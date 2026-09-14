<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** MARKETPLACE — moyen de versement enregistré par un vendeur. */
class MarketplacePayoutAccount extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'vendor_profile_id', 'method', 'label', 'details', 'is_default', 'verified_at', 'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'encrypted:array',
            'is_default' => 'boolean',
            'verified_at' => 'datetime',
            'changed_at' => 'datetime',
        ];
    }

    public function vendorProfile(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class);
    }
}
