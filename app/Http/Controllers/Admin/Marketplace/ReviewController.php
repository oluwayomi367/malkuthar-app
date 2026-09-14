<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceReview;
use App\Models\Marketplace\MarketplaceSuborder;
use App\Services\Marketplace\MarketplaceRefundService;
use App\Services\Marketplace\MarketplaceReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * MARKETPLACE — modération des avis + remboursement d'une sous-commande (admin).
 */
class ReviewController extends Controller
{
    public function __construct(
        private readonly MarketplaceReviewService $reviews,
        private readonly MarketplaceRefundService $refunds,
    ) {}

    public function moderate(Request $request, MarketplaceReview $review): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:published,hidden']]);
        $this->reviews->moderate($review, $data['status']);

        return back()->with('success', 'Avis mis à jour.');
    }

    public function refund(Request $request, MarketplaceSuborder $suborder): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);
        $this->refunds->refundSuborder($suborder, $data['reason'], $request->user());

        return back()->with('success', "Sous-commande {$suborder->suborder_number} remboursée, règlement repris.");
    }
}
