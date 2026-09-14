<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MARKETPLACE — ligne d'une sous-commande. Les champs texte sont des
 * snapshots pris à l'achat (l'offre peut évoluer ou disparaître ensuite).
 */
class MarketplaceOrderItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'marketplace_suborder_id', 'offer_id', 'offer_variant_id', 'type', 'title', 'variant_label',
        'unit_price', 'quantity', 'line_total', 'discount_amount', 'custom_fields',
        'reservation_datetime', 'reservation_end', 'fulfilment_status', 'download_count',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2', 'line_total' => 'decimal:2', 'discount_amount' => 'decimal:2',
            'quantity' => 'integer', 'custom_fields' => 'array',
            'reservation_datetime' => 'datetime', 'reservation_end' => 'datetime',
        ];
    }

    public function suborder(): BelongsTo
    {
        return $this->belongsTo(MarketplaceSuborder::class, 'marketplace_suborder_id');
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOffer::class, 'offer_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOfferVariant::class, 'offer_variant_id');
    }
}
