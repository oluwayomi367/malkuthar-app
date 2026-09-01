<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * §48-§49 - Les colonnes de solde sont un CACHE dénormalisé pour l'affichage.
 * La source de vérité est le ledger (ledgerEntries()). Ne jamais écrire
 * directement sur balance_available/balance_pending hors d'un Service
 * encapsulé dans DB::transaction() qui crée aussi la LedgerEntry associée.
 */
class Wallet extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'currency',
        'balance_available',
        'balance_pending',
        'total_earned',
        'total_withdrawn',
        'default_withdrawal_method',
        'default_withdrawal_account',
        'last_reconciled_at',
    ];

    protected function casts(): array
    {
        return [
            'balance_available' => 'decimal:2',
            'balance_pending' => 'decimal:2',
            'total_earned' => 'decimal:2',
            'total_withdrawn' => 'decimal:2',
            'default_withdrawal_account' => 'array',
            'last_reconciled_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registre de transactions du wallet (§48). Un Wallet a plusieurs LedgerEntries.
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * Historique des demandes de retrait liées à ce wallet.
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers métier
    |--------------------------------------------------------------------------
    */

    /**
     * §48 - Recalcule le solde disponible à partir du ledger, pour
     * réconciliation/audit (à ne pas confondre avec balance_available en cache).
     */
    public function computeAvailableBalanceFromLedger(): float
    {
        $credits = $this->ledgerEntries()
            ->where('direction', 'credit')
            ->where('status', 'available')
            ->sum('amount');

        $debits = $this->ledgerEntries()
            ->where('direction', 'debit')
            ->whereIn('status', ['processing', 'paid'])
            ->sum('amount');

        return (float) $credits - (float) $debits;
    }
}
