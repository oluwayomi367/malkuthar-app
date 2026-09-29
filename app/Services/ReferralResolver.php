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
    /**
     * Extrait le code de parrainage potentiel d'un lien d'affiliation ou du cookie.
     */
    public function codeFromLinkOrCookie(Request $request): ?string
    {
        $candidate = $request->query('ref')
            ?: $request->query('referral_code')
            ?: $request->query('referral')
            ?: $request->query('code')
            ?: $request->cookie(ReferralCookie::NAME);

        return is_string($candidate) && trim($candidate) !== '' ? trim($candidate) : null;
    }

    /**
     * Détermine si le code provient d'un lien de parrainage (ou cookie) valide,
     * auquel cas le champ doit être verrouillé (non modifiable).
     */
    public function isLocked(Request $request): bool
    {
        $code = $this->codeFromLinkOrCookie($request);
        if (! $code) {
            return false;
        }

        return $this->byCode($code) !== null;
    }

    public function fromRequest(Request $request, ?string $explicitCode = null): ?User
    {
        // 1. Si un cookie d'attribution officiel est posé, il prime pour garantir
        // que l'affilié référent initial n'est pas altéré côté client.
        $cookieCode = $request->cookie(ReferralCookie::NAME);
        if ($cookieCode && ($cookieSponsor = $this->byCode($cookieCode))) {
            return $cookieSponsor;
        }

        // 2. Si un paramètre de lien (query ref) est présent et valide
        $linkCode = $request->query('ref')
            ?: $request->query('referral_code')
            ?: $request->query('referral')
            ?: $request->query('code');
        if ($linkCode && ($linkSponsor = $this->byCode($linkCode))) {
            return $linkSponsor;
        }

        // 3. Saisie explicite dans le formulaire (si déverrouillé)
        $code = $explicitCode ?: $request->input('referral_code');

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
