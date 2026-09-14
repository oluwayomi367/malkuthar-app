<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * §72 — capture des paramètres UTM à l'arrivée sur le site pour mesurer
 * le parcours PUBLICITÉ → LANDING → PRODUIT → CHECKOUT → CONVERSION.
 * Stockés en session, repris par CheckoutService sur la commande.
 */
class CaptureUtmParameters
{
    private const KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    public function handle(Request $request, Closure $next): Response
    {
        $utm = array_filter($request->only(self::KEYS), fn ($v) => is_string($v) && $v !== '');

        if ($utm !== [] && $request->hasSession()) {
            // On garde la PREMIÈRE source d'acquisition (first-touch).
            $request->session()->put('utm', array_merge($utm, $request->session()->get('utm', [])));
        }

        return $next($request);
    }
}
