<?php

namespace App\Http\Middleware;

use App\Support\MarketplaceSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MARKETPLACE — réserve une route à un compte disposant d'un profil vendeur
 * ACTIF. À utiliser en plus de `auth`, jamais seul. Renvoie 404 si le module
 * marketplace est désactivé (le drapeau maître masque tout).
 */
class EnsureUserIsVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(MarketplaceSettings::enabled(), 404);

        $profile = $request->user()?->vendorProfile;

        abort_if($profile === null, 403, "Vous n'avez pas d'espace vendeur MALKUTHAR.");
        abort_unless($profile->status === 'active', 403, match ($profile->status) {
            'pending' => 'Votre demande de compte vendeur est en cours de validation.',
            'suspended' => 'Votre espace vendeur est suspendu.',
            default => "Votre espace vendeur n'est pas actif.",
        });

        $request->attributes->set('vendorProfile', $profile);

        return $next($request);
    }
}
