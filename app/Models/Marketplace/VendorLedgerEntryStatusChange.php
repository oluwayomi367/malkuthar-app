<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** MARKETPLACE — trace append-only des transitions de statut d'une écriture. */
class VendorLedgerEntryStatusChange extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = ['vendor_ledger_entry_id', 'from_status', 'to_status', 'context', 'reason', 'admin_id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
