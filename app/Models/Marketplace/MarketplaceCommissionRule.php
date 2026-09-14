<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * MARKETPLACE — règle de commission plateforme (barème configurable).
 * Résolue par App\Services\Marketplace\MarketplaceCommissionResolver.
 */
class MarketplaceCommissionRule extends Model
{
    use HasFactory, HasUuids;

    /** Spécificité croissante — sert au tri du résolveur. */
    public const SCOPE_WEIGHT = [
        'platform' => 0,
        'offer_type' => 1,
        'category' => 2,
        'vendor' => 3,
    ];

    protected $fillable = [
        'scope',
        'category_id',
        'vendor_profile_id',
        'offer_type',
        'rate',
        'min_fee',
        'max_fee',
        'starts_at',
        'ends_at',
        'priority',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'min_fee' => 'decimal:2',
            'max_fee' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'category_id');
    }

    public function vendorProfile(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCoveringDate(Builder $query, Carbon $at): Builder
    {
        return $query
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $at));
    }

    public function scopeWeight(): int
    {
        return self::SCOPE_WEIGHT[$this->scope] ?? 0;
    }

    /** Applique bornes min/max éventuelles à un montant de commission brut. */
    public function clampFee(float $fee): float
    {
        if ($this->min_fee !== null) {
            $fee = max($fee, (float) $this->min_fee);
        }
        if ($this->max_fee !== null) {
            $fee = min($fee, (float) $this->max_fee);
        }

        return round($fee, 2);
    }
}
