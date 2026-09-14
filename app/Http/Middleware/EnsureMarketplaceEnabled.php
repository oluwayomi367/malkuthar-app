<?php

namespace App\Http\Middleware;

use App\Support\MarketplaceSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MARKETPLACE — masque toute la surface du module tant que le drapeau maître
 * `marketplace.enabled` (Setting) est à false. Renvoie 404 (le module
 * « n'existe pas » pour le visiteur).
 */
class EnsureMarketplaceEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(MarketplaceSettings::enabled(), 404);

        return $next($request);
    }
}
