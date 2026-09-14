<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MARKETPLACE — détail d'une offre de type « digital ».
 */
class MarketplaceOfferDigital extends Model
{
    protected $table = 'marketplace_offer_digital';

    protected $primaryKey = 'offer_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'offer_id', 'delivery_type', 'file_path', 'file_size', 'file_original_name',
        'external_url', 'download_limit', 'link_ttl_days', 'version_label',
    ];

    protected function casts(): array
    {
        return ['file_size' => 'integer', 'download_limit' => 'integer', 'link_ttl_days' => 'integer'];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOffer::class, 'offer_id');
    }
}
