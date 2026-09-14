<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * OtpService — codes à usage unique pour les opérations sensibles (§55,
 * §56 : modification du moyen de retrait, et toute future 2FA).
 *
 * Le code n'est jamais stocké en clair, seulement son hash. Émettre un
 * nouveau code invalide silencieusement les précédents codes non
 * consommés du même "purpose" (un seul code valide à la fois).
 */
class OtpService
{
    private const MAX_ATTEMPTS = 5;

    public function issue(User $user, string $purpose, int $ttlMinutes = 10): string
    {
        return DB::transaction(function () use ($user, $purpose, $ttlMinutes) {
            OtpCode::query()
                ->where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->delete();

            $code = (string) random_int(100000, 999999);

            OtpCode::create([
                'user_id' => $user->id,
                'purpose' => $purpose,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]);

            return $code;
        });
    }

    /**
     * Vérifie et CONSOMME le code le plus récent pour ce user/purpose.
     * Limite le nombre de tentatives (§69 - limitation des tentatives).
     */
    public function verify(User $user, string $purpose, string $code): bool
    {
        return DB::transaction(function () use ($user, $purpose, $code) {
            $otp = OtpCode::query()
                ->where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->lockForUpdate()
                ->latest('created_at')
                ->first();

            if (! $otp || $otp->isExpired() || $otp->attempts >= self::MAX_ATTEMPTS) {
                return false;
            }

            $otp->increment('attempts');

            if (! Hash::check($code, $otp->code_hash)) {
                return false;
            }

            $otp->update(['consumed_at' => now()]);

            return true;
        });
    }
}
