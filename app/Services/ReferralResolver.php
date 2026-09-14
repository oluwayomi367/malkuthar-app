<?php

namespace App\Services;

use App\Models\User;
use App\Support\ReferralCookie;
use Illuminate\Http\Request;

/**
 * ReferralResolver — retrouve l'affilié référent à partir d'un code
 * explicite, du champ `referral_code` de la requête, ou du cookie
 * d'attribution posé par la route /ref/{code} (§33-§35, §39).
 *
 * Ne renvoie qu'un affilié ACTIF disposant d'un identifiant affilié.
 */
class ReferralResolver
{
    public function fromRequest(Request $request, ?string $explicitCode = null): ?User
    {
        $code = $explicitCode
            ?: $request->input('referral_code')
            ?: $request->cookie(ReferralCookie::NAME);

        return $this->byCode(is_string($code) ? $code : null);
    }

    public function byCode(?string $code): ?User
    {
        if (! $code) {
            return null;
        }

        return User::query()
            ->whereNotNull('affiliate_id')
            ->where('status', 'active')
            ->where('code_parrainage', $code)
            ->first();
    }
}
