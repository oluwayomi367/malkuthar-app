<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\MarketplaceSuborder;
use App\Models\Marketplace\VendorProfile;
use App\Services\Marketplace\VendorSettlementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MARKETPLACE — tableau de bord de l'espace vendeur.
 */
class VendorDashboardController extends Controller
{
    public function __construct(private readonly VendorSettlementService $settlement) {}

    public function __invoke(Request $request): View
    {
        /** @var VendorProfile $profile */
        $profile = $request->attributes->get('vendorProfile');

        $offerCounts = MarketplaceOffer::query()
            ->where('vendor_profile_id', $profile->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $balance = $this->settlement->recompute($profile);

        $subordersToShip = MarketplaceSuborder::query()
            ->where('vendor_profile_id', $profile->id)
            ->whereIn('fulfilment_status', ['unfulfilled', 'processing'])
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid'))
            ->count();

        $recentOrders = MarketplaceSuborder::query()
            ->where('vendor_profile_id', $profile->id)
            ->with('order:id,order_number,paid_at,payment_status')
            ->latest()
            ->limit(5)
            ->get();

        $recentOffers = MarketplaceOffer::query()
            ->where('vendor_profile_id', $profile->id)
            ->latest('updated_at')
            ->limit(5)
            ->get(['id', 'title', 'type', 'status', 'is_online', 'price', 'sale_price', 'currency', 'main_image_path', 'updated_at']);

        return view('vendor.dashboard', [
            'profile' => $profile->loadCount('documents'),
            'balance' => $balance,
            'stats' => [
                'offers_online' => (int) ($offerCounts['approved'] ?? 0),
                'offers_pending' => (int) ($offerCounts['pending_review'] ?? 0),
                'offers_changes' => (int) ($offerCounts['changes_requested'] ?? 0),
                'offers_draft' => (int) ($offerCounts['draft'] ?? 0),
                'to_ship' => $subordersToShip,
            ],
            'recentOrders' => $recentOrders,
            'recentOffers' => $recentOffers,
            'checklist' => [
                ['done' => (bool) $profile->logo_path, 'label' => 'Ajouter un logo à votre boutique', 'route' => 'vendor.profile.edit'],
                ['done' => $profile->payoutAccounts()->exists(), 'label' => 'Enregistrer un moyen de versement', 'route' => 'vendor.payouts.index'],
                ['done' => MarketplaceOffer::where('vendor_profile_id', $profile->id)->exists(), 'label' => 'Créer votre première offre', 'route' => 'vendor.offers.create'],
                ['done' => MarketplaceOffer::where('vendor_profile_id', $profile->id)->where('status', 'approved')->exists(), 'label' => 'Publier une offre (validée par l\'équipe)', 'route' => 'vendor.offers.index'],
            ],
        ]);
    }
}
