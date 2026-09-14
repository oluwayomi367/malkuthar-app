<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\VendorProfile;
use App\Support\MarketplaceSettings;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MARKETPLACE — page publique « /marketplace » : présentation du programme
 * vendeur et tarifs. Avant l'ouverture (`marketplace.enabled` = false), rend
 * la version « liste d'attente » ; une fois ouverte, la version « en ligne »
 * avec statistiques réelles et les paliers de commission (voir
 * config/marketplace.php + MarketplaceSettings).
 */
class MarketplaceLandingController extends Controller
{
    public function __invoke(): Response
    {
        $enabled = MarketplaceSettings::enabled();

        $stats = null;
        if ($enabled) {
            $stats = [
                'vendors' => VendorProfile::query()->active()->count(),
                'offers' => MarketplaceOffer::query()->publiclyVisible()->count(),
            ];
        }

        return Inertia::render('Marketplace/Landing', [
            'enabled' => $enabled,
            'stats' => $stats,
            'starterRate' => MarketplaceSettings::defaultCommissionRate() * 100,
            'growthRate' => MarketplaceSettings::growthCommissionRate() * 100,
            'growthThreshold' => MarketplaceSettings::growthThresholdAmount(),
        ]);
    }
}
