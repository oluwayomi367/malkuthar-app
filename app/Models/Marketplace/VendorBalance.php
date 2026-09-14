<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MARKETPLACE — cache du solde vendeur. La source de vérité est
 * vendor_ledger_entries ; ce modèle est recalculable à tout moment.
 */
class VendorBalance extends Model
{
    protected $primaryKey = 'vendor_profile_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'vendor_profile_id', 'currency',
        'pending_amount', 'available_amount', 'reserved_amount',
        'lifetime_earned', 'lifetime_paid', 'lifetime_reversed', 'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'pending_amount' => 'decimal:2', 'available_amount' => 'decimal:2',
            'reserved_amount' => 'decimal:2', 'lifetime_earned' => 'decimal:2',
            'lifetime_paid' => 'decimal:2', 'lifetime_reversed' => 'decimal:2',
            'updated_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class, 'vendor_profile_id');
    }
}
