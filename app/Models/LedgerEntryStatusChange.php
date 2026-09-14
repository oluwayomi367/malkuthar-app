<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * §41 — une ligne = une transition de statut d'une écriture du registre.
 * Append-only (pas d'updated_at).
 */
class LedgerEntryStatusChange extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'ledger_entry_id',
        'from_status',
        'to_status',
        'context',
        'reason',
        'admin_id',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
