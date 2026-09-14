<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'referring_affiliate_id',
        'coupon_id',
        'coupon_code',
        'order_number',
        'status',
        'payment_status',
        'payment_method',
        'payment_provider',
        'payment_reference',
        'cod_collected_at',
        'cod_collected_by',
        'currency',
        'subtotal',
        'discount_total',
        'shipping_fee',
        'total_amount',
        'has_digital_items',
        'has_physical_items',
        'has_service_items',
        'shipping_address',
        'shipping_carrier',
        'tracking_number',
        'shipping_status',
        'notes',
        'utm_data',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'has_digital_items' => 'boolean',
            'has_physical_items' => 'boolean',
            'has_service_items' => 'boolean',
            'shipping_address' => 'array',
            'utm_data' => 'array',
            'cod_collected_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Client ayant passé la commande.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Affilié référent ayant généré la vente (§39), s'il y en a un.
     */
    public function referringAffiliate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referring_affiliate_id');
    }

    /**
     * Coupon appliqué à la commande (§79), s'il y en a un.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Administrateur ayant déclaré l'encaissement d'un paiement à la
     * livraison, s'il y a lieu.
     */
    public function codCollector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cod_collected_by');
    }

    /**
     * Lignes de la commande. Un Order a plusieurs OrderItems.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Écritures de commission générées par cette commande (§66).
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
     * Commande réglée « à la livraison » : aucun PSP n'est sollicité, le
     * paiement est déclaré encaissé par un administrateur (§22/§26).
     */
    public function isCashOnDelivery(): bool
    {
        return $this->payment_provider === 'cod';
    }

    /**
     * §5 - Montant total sur lequel des commissions doivent être calculées
     * (uniquement les lignes digitales affiliables).
     */
    public function commissionEligibleAmount(): float
    {
        return (float) $this->items()
            ->where('product_type', 'digital')
            ->where('is_affiliable_snapshot', true)
            ->sum('commission_base_amount');
    }
}
