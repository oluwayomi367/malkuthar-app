<?php

namespace App\Http\Controllers;

use App\Services\ReferralResolver;
use App\Support\ReferralCookie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * §33 — Lien personnel d'affiliation : https://.../ref/{code_parrainage}
 *
 * On pose un cookie d'attribution (durée configurable, §35) puis on
 * redirige le visiteur vers la cible souhaitée (paramètre ?to=, sinon
 * l'accueil). Un code inconnu redirige sans poser de cookie.
 */
class ReferralController extends Controller
{
    public function __construct(private readonly ReferralResolver $resolver) {}

    public function capture(Request $request, string $code): RedirectResponse
    {
        $affiliate = $this->resolver->byCode($code);

        $target = $this->safeTarget($request->query('to'));
        $response = redirect()->to($target);

        if ($affiliate) {
            $response->withCookie(cookie(
                name: ReferralCookie::NAME,
                value: $affiliate->code_parrainage,
                minutes: ReferralCookie::minutes(),
                httpOnly: true,
            ));
        }

        return $response;
    }

    /**
     * N'autorise qu'une redirection interne (évite l'open-redirect).
     */
    private function safeTarget(?string $to): string
    {
        if (is_string($to) && str_starts_with($to, '/') && ! str_starts_with($to, '//')) {
            return $to;
        }

        return '/';
    }
}
