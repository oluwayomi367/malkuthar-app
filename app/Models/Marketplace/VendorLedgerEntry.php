<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MARKETPLACE — écriture immuable du registre de règlement d'un vendeur.
 * Le montant n'est jamais modifié après création ; seul le `status` évolue,
 * via transitionTo() (journalisé, patron §41).
 */
class VendorLedgerEntry extends Model
{
    use HasFactory, HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'vendor_profile_id', 'reference', 'direction', 'source_type',
        'marketplace_suborder_id', 'vendor_payout_id',
        'amount', 'currency', 'status', 'available_at',
        'description', 'admin_id', 'adjustment_reason', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'available_at' => 'datetime',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class, 'vendor_profile_id');
    }

    public function suborder(): BelongsTo
    {
        return $this->belongsTo(MarketplaceSuborder::class, 'marketplace_suborder_id');
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(VendorPayout::class, 'vendor_payout_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(VendorLedgerEntryStatusChange::class)->orderBy('created_at');
    }

    public function transitionTo(string $status, ?string $context = null, ?string $reason = null, ?string $adminId = null): bool
    {
        if ($this->status === $status) {
            return false;
        }

        $from = $this->status;
        $this->forceFill(['status' => $status])->save();

        $this->statusChanges()->create([
            'from_status' => $from,
            'to_status' => $status,
            'context' => $context,
            'reason' => $reason,
            'admin_id' => $adminId,
        ]);

        return true;
    }

    protected static function booted(): void
    {
        static::created(function (VendorLedgerEntry $entry) {
            $entry->statusChanges()->create([
                'from_status' => null,
                'to_status' => $entry->status,
                'context' => 'created',
            ]);
        });
    }

    /** Montant signé (+ crédit / − débit). */
    public function signedAmount(): float
    {
        return $this->direction === 'credit' ? (float) $this->amount : -(float) $this->amount;
    }

    public function scopeForVendor(Builder $q, string $vendorId): Builder
    {
        return $q->where('vendor_profile_id', $vendorId);
    }
}
