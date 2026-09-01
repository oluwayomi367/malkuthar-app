<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_type',
        'is_affiliable_snapshot',
        'quantity',
        'unit_price',
        'discount_amount',
        'total_price',
        'currency',
        'commission_base_amount',
        'variant_sku',
        'variant_options',
        'reservation_datetime',
    ];

    protected function casts(): array
    {
        return [
            'is_affiliable_snapshot' => 'boolean',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_price' => 'decimal:2',
            'commission_base_amount' => 'decimal:2',
            'variant_options' => 'array',
            'reservation_datetime' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Écritures de commission (niveau 1/2/3) rattachées à cette ligne (§39).
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers métier
    |--------------------------------------------------------------------------
    */

    /**
     * §4-§5 - Seule une ligne digitale marquée affiliable au moment de
     * l'achat peut générer une commission.
     */
    public function isCommissionEligible(): bool
    {
        return $this->product_type === 'digital' && $this->is_affiliable_snapshot === true;
    }
}
