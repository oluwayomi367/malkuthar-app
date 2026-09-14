<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MARKETPLACE — détail d'une offre de type « service ».
 */
class MarketplaceOfferService extends Model
{
    protected $table = 'marketplace_offer_service';

    protected $primaryKey = 'offer_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'offer_id', 'duration_minutes', 'delivery_mode', 'location', 'service_area',
        'buffer_minutes', 'min_notice_hours', 'max_advance_days', 'capacity_per_slot',
        'booking_confirmation', 'timezone',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer', 'buffer_minutes' => 'integer',
            'min_notice_hours' => 'integer', 'max_advance_days' => 'integer',
            'capacity_per_slot' => 'integer',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOffer::class, 'offer_id');
    }
}
