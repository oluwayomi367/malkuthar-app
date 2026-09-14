<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

/**
 * MARKETPLACE — commande acheteur (vue globale d'un panier multi-vendeurs).
 */
class MarketplaceOrder extends Model
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    /** Notifications transac. adressées à l'email de l'acheteur (invité inclus). */
    public function routeNotificationForMail(): string
    {
        return $this->buyer_email;
    }

    protected $fillable = [
        'order_number', 'buyer_id', 'buyer_email', 'buyer_phone',
        'currency', 'items_total', 'shipping_total', 'discount_total', 'grand_total',
        'payment_status', 'payment_provider', 'payment_reference', 'paid_at',
        'status', 'shipping_address', 'utm',
    ];

    protected function casts(): array
    {
        return [
            'items_total' => 'decimal:2', 'shipping_total' => 'decimal:2',
            'discount_total' => 'decimal:2', 'grand_total' => 'decimal:2',
            'paid_at' => 'datetime', 'shipping_address' => 'array', 'utm' => 'array',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function suborders(): HasMany
    {
        return $this->hasMany(MarketplaceSuborder::class);
    }

    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(MarketplaceOrderItem::class, MarketplaceSuborder::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }
}
