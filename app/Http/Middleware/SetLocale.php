<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * §75 — résolution de la langue : paramètre ?lang, puis cookie, puis
 * en-tête Accept-Language, dans la limite des langues supportées.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = (array) config('malkuthar.i18n.supported_locales', ['fr']);
        $default = (string) config('malkuthar.i18n.default_locale', 'fr');

        $requested = $request->query('lang')
            ?? $request->cookie('mal_locale')
            ?? $request->getPreferredLanguage($supported);

        $locale = in_array($requested, $supported, true) ? $requested : $default;

        App::setLocale($locale);

        $response = $next($request);

        if ($request->query('lang') && in_array($request->query('lang'), $supported, true)) {
            $response->headers->setCookie(cookie('mal_locale', $locale, 60 * 24 * 365));
        }

        return $response;
    }
}
