<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MARKETPLACE — détail d'une offre de type « formation ».
 */
class MarketplaceOfferTraining extends Model
{
    protected $table = 'marketplace_offer_training';

    protected $primaryKey = 'offer_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'offer_id', 'format', 'level', 'language', 'total_duration_minutes', 'certificate',
        'cohort_starts_at', 'cohort_ends_at', 'seats', 'seats_taken', 'access_days',
        'curriculum', 'drip_enabled',
    ];

    protected function casts(): array
    {
        return [
            'certificate' => 'boolean', 'drip_enabled' => 'boolean',
            'cohort_starts_at' => 'datetime', 'cohort_ends_at' => 'datetime',
            'seats' => 'integer', 'seats_taken' => 'integer',
            'total_duration_minutes' => 'integer', 'access_days' => 'integer',
            'curriculum' => 'array',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOffer::class, 'offer_id');
    }
}
