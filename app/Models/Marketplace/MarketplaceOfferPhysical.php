<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MARKETPLACE — détail d'une offre de type « physique ».
 */
class MarketplaceOfferPhysical extends Model
{
    protected $table = 'marketplace_offer_physical';

    protected $primaryKey = 'offer_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'offer_id', 'sku', 'barcode', 'stock', 'stock_reserved', 'low_stock_threshold',
        'weight_grams', 'length_mm', 'width_mm', 'height_mm',
        'shipping_class', 'handling_days', 'has_variants', 'origin_region',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer', 'stock_reserved' => 'integer', 'low_stock_threshold' => 'integer',
            'weight_grams' => 'integer', 'has_variants' => 'boolean',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOffer::class, 'offer_id');
    }
}
