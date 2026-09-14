<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MARKETPLACE — sous-commande : la part d'un vendeur dans une commande.
 * Unité de règlement (commission figée) et de traitement logistique.
 */
class MarketplaceSuborder extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'suborder_number', 'marketplace_order_id', 'vendor_profile_id',
        'items_total', 'shipping_total', 'discount_total', 'vendor_gross',
        'commission_rule_id', 'commission_rate', 'commission_amount', 'commission_source',
        'payment_fee_share', 'vendor_net',
        'settlement_status', 'available_at',
        'fulfilment_status', 'shipping_carrier', 'tracking_number',
    ];

    protected function casts(): array
    {
        return [
            'items_total' => 'decimal:2', 'shipping_total' => 'decimal:2',
            'discount_total' => 'decimal:2', 'vendor_gross' => 'decimal:2',
            'commission_rate' => 'decimal:4', 'commission_amount' => 'decimal:2',
            'payment_fee_share' => 'decimal:2', 'vendor_net' => 'decimal:2',
            'available_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class, 'vendor_profile_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }
}
