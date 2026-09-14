<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** MARKETPLACE — avis acheteur sur une offre. */
class MarketplaceReview extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'offer_id', 'vendor_profile_id', 'marketplace_order_item_id', 'user_id',
        'rating', 'title', 'body', 'status', 'vendor_reply', 'vendor_replied_at',
    ];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'vendor_replied_at' => 'datetime'];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOffer::class, 'offer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrderItem::class, 'marketplace_order_item_id');
    }
}
