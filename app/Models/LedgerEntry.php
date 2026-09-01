<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * §48-§49 - Ligne immuable du registre financier. Ne doit jamais être
 * modifiée après création : toute correction se fait via une nouvelle
 * ligne d'ajustement (source_type=adjustment) qui référence admin_id et
 * adjustment_reason.
 */
class LedgerEntry extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'wallet_id',
        'user_id',
        'reference',
        'direction',
        'source_type',
        'source_id',
        'order_id',
        'order_item_id',
        'withdrawal_id',
        'level',
        'amount',
        'currency',
        'status',
        'description',
        'admin_id',
        'adjustment_reason',
        'metadata',
        'available_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'level' => 'integer',
            'metadata' => 'array',
            'available_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Bénéficiaire (affilié) de cette écriture.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(Withdrawal::class);
    }

    /**
     * Administrateur responsable, uniquement pour source_type=adjustment (§49).
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForLevel($query, int $level)
    {
        return $query->where('level', $level);
    }
}
