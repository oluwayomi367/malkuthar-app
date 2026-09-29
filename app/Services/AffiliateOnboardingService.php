<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Support\Sequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * AffiliateOnboardingService — §30 à §38 du cahier des charges.
 *
 *  - §30 : un client existant devient affilié sans créer de 2e compte.
 *  - §32 : identifiant affilié unique et permanent (MAL-AFF-000001...).
 *  - §33-§34 : lien personnel + code de parrainage uniques.
 *  - §35 : le parrain est rattaché une seule fois et n'est plus
 *          modifiable ensuite (aucune méthode publique ne le change).
 *  - §36 : l'arbre se construit automatiquement via User::parent_id.
 *  - §38 : garde-fous anti auto-parrainage et anti-cycle.
 *  - §43 : chaque affilié dispose d'un portefeuille interne.
 */
class AffiliateOnboardingService
{
    public function __construct(private readonly Sequence $sequence) {}

    /**
     * Active l'espace affilié d'un compte existant. Idempotent : si le
     * compte est déjà affilié, il est renvoyé inchangé.
     */
    public function activate(User $user): User
    {
        if ($user->isAffiliate()) {
            return $user;
        }

        return DB::transaction(function () use ($user) {
            /** @var User $user */
            $user = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if ($user->isAffiliate()) {
                return $user;
            }

            $user->forceFill([
                'affiliate_id' => $this->nextAffiliateId(),
                'code_parrainage' => $this->generateReferralCode($user),
                'affiliate_activated_at' => now(),
            ])->save();

            // §43 — portefeuille interne (registre = ledger_entries).
            Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['currency' => $user->currency ?? 'XOF'],
            );

            // Si l'utilisateur n'a pas encore de parrain, le Système devient d'office son parrain
            if (! $user->parent_id && $user->getKey() !== $this->systemUser()->getKey()) {
                $this->attributeSponsor($user);
            }

            return $user->refresh();
        });
    }

    /**
     * Résout ou crée le compte parrain officiel « Système » de MALKUTHAR.
     */
    public function systemUser(): User
    {
        $email = (string) config('malkuthar.affiliation.system_email', 'system@malkuthar.com');
        $code = (string) config('malkuthar.affiliation.system_referral_code', 'MALKUTHAR');

        $system = User::query()->where('email', $email)->first();

        if ($system) {
            if (! $system->isAffiliate()) {
                $system->forceFill([
                    'affiliate_id' => 'MAL-AFF-000000',
                    'code_parrainage' => $code,
                    'affiliate_activated_at' => now(),
                    'status' => 'active',
                ])->save();

                Wallet::firstOrCreate(
                    ['user_id' => $system->id],
                    ['currency' => $system->currency ?? 'XOF'],
                );
            }

            return $system;
        }

        $system = User::create([
            'first_name' => 'Système',
            'last_name' => 'MALKUTHAR',
            'email' => $email,
            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
            'role' => 'admin',
            'status' => 'active',
            'currency' => 'XOF',
            'affiliate_id' => 'MAL-AFF-000000',
            'code_parrainage' => $code,
            'affiliate_activated_at' => now(),
        ]);

        Wallet::firstOrCreate(
            ['user_id' => $system->id],
            ['currency' => 'XOF'],
        );

        return $system;
    }

    /**
     * §35-§38 — Rattache définitivement $user au parrain $sponsor.
     * Si aucun parrain n'est fourni ($sponsor === null), le Système
     * devient d'office son parrain.
     *
     * Ne fait rien (sans lever d'exception) si le rattachement est
     * impossible ou interdit :
     *  - $user a déjà un parrain (immuable, §35) ;
     *  - auto-parrainage (§38) ;
     *  - parrain non actif ou non affilié ;
     *  - le rattachement créerait un cycle dans l'arbre (§38).
     *
     * @return bool true si le rattachement a été effectué.
     */
    public function attributeSponsor(User $user, ?User $sponsor = null): bool
    {
        if ($user->parent_id) {
            return false;
        }

        if ($sponsor !== null) {
            if ($sponsor->getKey() === $user->getKey()) {
                return false; // §38 — auto-parrainage
            }

            if ($sponsor->status !== 'active' || ! $sponsor->isAffiliate()) {
                return false;
            }

            if ($this->wouldCreateCycle($user, $sponsor)) {
                return false; // §38 — manipulation de l'arbre
            }

            $effectiveSponsor = $sponsor;
        } else {
            $effectiveSponsor = $this->systemUser();

            if ($effectiveSponsor->getKey() === $user->getKey()) {
                return false;
            }

            if ($this->wouldCreateCycle($user, $effectiveSponsor)) {
                return false;
            }
        }

        $user->forceFill(['parent_id' => $effectiveSponsor->getKey()])->save();

        return true;
    }

    /**
     * §32 — Identifiant affilié séquentiel, unique et permanent.
     */
    private function nextAffiliateId(): string
    {
        $prefix = (string) config('malkuthar.affiliation.affiliate_id_prefix', 'MAL-AFF-');
        $padding = (int) config('malkuthar.affiliation.affiliate_id_padding', 6);

        $n = $this->sequence->next('affiliate_id');

        return $prefix.str_pad((string) $n, $padding, '0', STR_PAD_LEFT);
    }

    /**
     * §34 — Code de parrainage unique, dérivé du prénom + suffixe
     * numérique, régénéré en cas de collision.
     */
    private function generateReferralCode(User $user): string
    {
        $base = Str::of($user->first_name ?: 'MAL')
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]/', '')
            ->substr(0, 8);

        if ($base->isEmpty()) {
            $base = Str::of('MAL');
        }

        do {
            $code = $base->value().random_int(100, 999);
        } while (User::query()->where('code_parrainage', $code)->exists());

        return $code;
    }

    /**
     * Remonte la chaîne de parrainage à partir de $sponsor ; si l'on
     * retombe sur $user, rattacher $user à $sponsor fermerait une boucle.
     */
    private function wouldCreateCycle(User $user, User $sponsor): bool
    {
        $current = $sponsor;
        $guard = 0;

        while ($current && $guard++ < 100) {
            if ($current->getKey() === $user->getKey()) {
                return true;
            }

            $current = $current->parent;
        }

        return false;
    }
}
