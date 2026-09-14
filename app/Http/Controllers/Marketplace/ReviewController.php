<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceOrderItem;
use App\Services\Marketplace\MarketplaceReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * MARKETPLACE — dépôt d'un avis acheteur (preuve d'achat requise).
 */
class ReviewController extends Controller
{
    public function __construct(private readonly MarketplaceReviewService $reviews) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_item_id' => ['required', 'exists:marketplace_order_items,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $item = MarketplaceOrderItem::with('suborder.order', 'offer')->findOrFail($data['order_item_id']);

        try {
            $review = $this->reviews->create(
                $item, $request->user(), (int) $data['rating'], $data['title'] ?? null, $data['body'] ?? null,
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('marketplace.shop.offer', $review->offer->slug)
            ->with('success', 'Merci pour votre avis.');
    }
}
