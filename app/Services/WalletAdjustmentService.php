<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * WalletAdjustmentService — §49 : "Pour effectuer une correction, le
 * système doit créer une transaction d'ajustement comprenant :
 * administrateur responsable, date, montant, motif, référence,
 * historique." Le solde n'est JAMAIS écrit directement.
 */
class WalletAdjustmentService
{
    public function adjust(User $admin, User $affiliate, float $amount, string $direction, string $reason): LedgerEntry
    {
        $amount = round(abs($amount), 2);

        return DB::transaction(function () use ($admin, $affiliate, $amount, $direction, $reason) {
            $wallet = Wallet::query()
                ->firstOrCreate(['user_id' => $affiliate->id], ['currency' => $affiliate->currency ?? 'XOF']);

            $wallet = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();

            $entry = LedgerEntry::create([
                'wallet_id' => $wallet->id,
                'user_id' => $affiliate->id,
                'reference' => 'ADJ-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
                'direction' => $direction,
                'source_type' => 'adjustment',
                'status' => 'available',
                'amount' => $amount,
                'currency' => $wallet->currency,
                'admin_id' => $admin->id,
                'adjustment_reason' => $reason,
                'description' => sprintf(
                    'Ajustement manuel (%s) par %s %s : %s',
                    $direction === 'credit' ? '+' : '-',
                    $admin->first_name,
                    $admin->last_name,
                    $reason
                ),
            ]);

            if ($direction === 'credit') {
                $wallet->increment('balance_available', $amount);
            } else {
                $wallet->decrement('balance_available', $amount);
            }

            return $entry;
        });
    }
}
