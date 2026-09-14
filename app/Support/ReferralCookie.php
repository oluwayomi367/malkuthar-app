<?php

namespace App\Support;

/**
 * §33-§35 — Cookie d'attribution du parrain.
 *
 * Posé quand un visiteur arrive via https://.../ref/{code_parrainage}.
 * Lu à l'inscription (rattachement définitif du parrain, §35) et au
 * checkout (renseignement de Order::referring_affiliate_id, §39).
 */
class ReferralCookie
{
    public const NAME = 'mal_ref';

    public static function minutes(): int
    {
        return max(1, (int) config('malkuthar.affiliation.referral_cookie_days', 30)) * 24 * 60;
    }
}
