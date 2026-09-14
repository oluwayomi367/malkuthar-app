<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** MARKETPLACE — demande de versement d'un vendeur. */
class VendorPayout extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reference', 'vendor_profile_id', 'payout_account_id',
        'amount', 'fee', 'net_amount', 'currency', 'method', 'destination',
        'status', 'period_start', 'period_end', 'requested_by', 'approved_by',
        'provider', 'provider_reference', 'failure_reason', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2', 'fee' => 'decimal:2', 'net_amount' => 'decimal:2',
            'destination' => 'array',
            'period_start' => 'datetime', 'period_end' => 'datetime', 'processed_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class, 'vendor_profile_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplacePayoutAccount::class, 'payout_account_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(VendorLedgerEntry::class, 'vendor_payout_id');
    }
}
