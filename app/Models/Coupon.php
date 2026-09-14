<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * §79 — coupon de réduction (pourcentage ou montant fixe), limité dans le
 * temps, en nombre d'utilisations, et éventuellement à certains produits
 * ou catégories.
 */
class Coupon extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code', 'type', 'value', 'starts_at', 'expires_at',
        'max_uses', 'used_count', 'min_order_amount',
        'product_ids', 'categories', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'product_ids' => 'array',
            'categories' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function isCurrentlyValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function appliesTo(Product $product): bool
    {
        if (is_array($this->product_ids) && count($this->product_ids) > 0) {
            return in_array($product->id, $this->product_ids, true);
        }

        if (is_array($this->categories) && count($this->categories) > 0) {
            return in_array($product->category, $this->categories, true);
        }

        return true; // aucun ciblage = tous les produits
    }

    /**
     * Remise appliquée à un total de ligne donné (jamais supérieure au
     * total lui-même).
     */
    public function discountForLine(float $lineTotal): float
    {
        $discount = $this->type === 'percent'
            ? $lineTotal * ((float) $this->value / 100)
            : (float) $this->value;

        return round(min($discount, $lineTotal), 2);
    }
}
