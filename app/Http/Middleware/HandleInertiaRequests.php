<?php

namespace App\Http\Middleware;

use App\Services\CommissionRateResolver;
use App\Support\Catalog;
use App\Support\MarketplaceSettings;
use App\Support\StorefrontSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Vitrine publique (React/Inertia) — voir resources/js/app.jsx. Panier,
 * checkout, admin, espace vendeur/affilié restent en Blade + Alpine
 * (layouts/*.blade.php), inchangés et sans rapport avec ce middleware.
 */
class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => trim("{$user->first_name} {$user->last_name}"),
                    'email' => $user->email,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'status' => fn () => $request->session()->get('status'),
            ],
            'categories' => Catalog::categories(),
            'marketplaceEnabled' => MarketplaceSettings::enabled(),
            // §27 — taux en vigueur (campagne éventuelle), pour le badge
            // « produit affiliable » comme pour la page /affiliation.
            'commissionRates' => app(CommissionRateResolver::class)->percentages(),
            'announcementTicker' => StorefrontSettings::announcementTicker(),
            'announcementTickerEnabled' => StorefrontSettings::announcementTickerEnabled(),
            // Numéro du bouton WhatsApp flottant (chiffres seuls, format wa.me).
            'whatsapp' => preg_replace('/\D+/', '', (string) config('malkuthar.contact.whatsapp')) ?: null,
        ];
    }
}
