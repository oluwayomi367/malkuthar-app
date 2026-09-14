<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceOrderItem;
use App\Models\Marketplace\MarketplaceReview;
use App\Models\Marketplace\MarketplaceSuborder;
use App\Services\Marketplace\MarketplaceFulfilmentService;
use App\Services\Marketplace\MarketplaceReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MARKETPLACE — le vendeur consulte et traite ses sous-commandes.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly MarketplaceFulfilmentService $fulfilment,
        private readonly MarketplaceReviewService $reviews,
    ) {}

    public function index(Request $request): View
    {
        $vendor = $request->attributes->get('vendorProfile');

        $suborders = MarketplaceSuborder::query()
            ->where('vendor_profile_id', $vendor->id)
            ->with('order:id,order_number,buyer_email,paid_at,payment_status', 'items')
            ->when($request->query('fulfilment'), fn ($q, $s) => $q->where('fulfilment_status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('vendor.orders.index', compact('suborders'));
    }

    public function show(Request $request, MarketplaceSuborder $suborder): View
    {
        $this->authorizeSuborder($request, $suborder);
        $suborder->load('order', 'items.offer');

        return view('vendor.orders.show', [
            'suborder' => $suborder,
            'reviews' => MarketplaceReview::where('vendor_profile_id', $suborder->vendor_profile_id)
                ->whereIn('offer_id', $suborder->items->pluck('offer_id')->filter())
                ->latest()->get(),
        ]);
    }

    public function ship(Request $request, MarketplaceSuborder $suborder): RedirectResponse
    {
        $this->authorizeSuborder($request, $suborder);
        $data = $request->validate([
            'carrier' => ['nullable', 'string', 'max:120'],
            'tracking' => ['nullable', 'string', 'max:120'],
        ]);

        $this->fulfilment->ship($suborder, $data['carrier'] ?? null, $data['tracking'] ?? null, $request->user());

        return back()->with('success', 'Sous-commande marquée expédiée.');
    }

    public function advance(Request $request, MarketplaceSuborder $suborder): RedirectResponse
    {
        $this->authorizeSuborder($request, $suborder);
        $data = $request->validate(['to' => ['required', 'in:processing,shipped,delivered,completed']]);

        $this->fulfilment->advance($suborder, $data['to'], $request->user());

        return back()->with('success', 'Statut mis à jour.');
    }

    public function confirmBooking(Request $request, MarketplaceSuborder $suborder, MarketplaceOrderItem $item): RedirectResponse
    {
        $this->authorizeSuborder($request, $suborder);
        abort_unless($item->marketplace_suborder_id === $suborder->id, 404);

        $this->fulfilment->confirmBooking($item, $request->user());

        return back()->with('success', 'Rendez-vous confirmé.');
    }

    public function replyReview(Request $request, MarketplaceReview $review): RedirectResponse
    {
        abort_unless(
            $review->vendor_profile_id === $request->attributes->get('vendorProfile')->id,
            403,
        );
        $data = $request->validate(['reply' => ['required', 'string', 'min:2', 'max:1000']]);

        $this->reviews->reply($review, $data['reply']);

        return back()->with('success', 'Réponse publiée.');
    }

    private function authorizeSuborder(Request $request, MarketplaceSuborder $suborder): void
    {
        abort_unless(
            $suborder->vendor_profile_id === $request->attributes->get('vendorProfile')->id,
            403,
        );
    }
}
