<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'commission_rate',
        'commission_campaign_id',
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
            'commission_rate' => 'decimal:4',
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

    /**
     * §41 — journal des changements de statut de cette écriture.
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(LedgerEntryStatusChange::class)->orderBy('created_at');
    }

    /**
     * §41 — change le statut ET enregistre la transition. À utiliser
     * partout à la place d'un `update(['status' => ...])` direct.
     */
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
        // §41 — le statut initial d'une écriture est lui aussi consigné.
        static::created(function (LedgerEntry $entry) {
            $entry->statusChanges()->create([
                'from_status' => null,
                'to_status' => $entry->status,
                'context' => 'created',
            ]);
        });
    }

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
