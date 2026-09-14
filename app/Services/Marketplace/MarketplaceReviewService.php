<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplaceOrderItem;
use App\Models\Marketplace\MarketplaceReview;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * MARKETPLACE — avis acheteurs : dépôt (preuve d'achat), réponse vendeur,
 * modération admin. La note moyenne de l'offre est recalculée à chaque
 * changement d'état.
 */
class MarketplaceReviewService
{
    public function create(MarketplaceOrderItem $item, User $buyer, int $rating, ?string $title, ?string $body): MarketplaceReview
    {
        $suborder = $item->suborder;

        if ($suborder->order->buyer_id !== $buyer->getKey()) {
            throw ValidationException::withMessages(['review' => 'Cet article ne figure pas dans vos commandes.']);
        }
        if ($suborder->order->payment_status !== 'paid') {
            throw ValidationException::withMessages(['review' => 'Commande non payée.']);
        }
        if (MarketplaceReview::where('marketplace_order_item_id', $item->id)->exists()) {
            throw ValidationException::withMessages(['review' => 'Vous avez déjà laissé un avis pour cet article.']);
        }

        $review = MarketplaceReview::create([
            'offer_id' => $item->offer_id,
            'vendor_profile_id' => $suborder->vendor_profile_id,
            'marketplace_order_item_id' => $item->id,
            'user_id' => $buyer->getKey(),
            'rating' => max(1, min(5, $rating)),
            'title' => $title,
            'body' => $body,
            'status' => 'published',
        ]);

        $review->offer?->recomputeRating();

        return $review;
    }

    public function reply(MarketplaceReview $review, string $reply): MarketplaceReview
    {
        $review->update(['vendor_reply' => $reply, 'vendor_replied_at' => now()]);

        return $review;
    }

    public function moderate(MarketplaceReview $review, string $status): MarketplaceReview
    {
        abort_unless(in_array($status, ['published', 'hidden'], true), 422);
        $review->update(['status' => $status]);
        $review->offer?->recomputeRating();

        return $review;
    }
}
