<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplaceSuborder;
use App\Models\Marketplace\VendorBalance;
use App\Models\Marketplace\VendorLedgerEntry;
use App\Models\Marketplace\VendorProfile;
use App\Models\User;
use App\Support\MarketplaceSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * MARKETPLACE — moteur de règlement des vendeurs.
 *
 * Registre immuable (`vendor_ledger_entries`) + cache dérivé
 * (`vendor_balances`). Aucune écriture directe du solde ailleurs.
 */
class VendorSettlementService
{
    /**
     * À la confirmation de paiement : crédite le net vendeur d'une
     * sous-commande, en attente jusqu'à `available_at`. Idempotent.
     */
    public function recordSale(MarketplaceSuborder $sub): VendorLedgerEntry
    {
        $entry = DB::transaction(function () use ($sub) {
            $entry = VendorLedgerEntry::firstOrCreate(
                ['reference' => 'sale:'.$sub->id],
                [
                    'vendor_profile_id' => $sub->vendor_profile_id,
                    'direction' => 'credit',
                    'source_type' => 'sale',
                    'marketplace_suborder_id' => $sub->id,
                    'amount' => $sub->vendor_net,
                    'currency' => $sub->order->currency,
                    'status' => 'pending',
                    'available_at' => $sub->available_at ?? now()->addDays(
                        MarketplaceSettings::settlementHoldDays()
                    ),
                    'description' => "Vente {$sub->suborder_number}",
                ],
            );

            return $entry;
        });

        $this->recompute($sub->vendor);

        return $entry;
    }

    /**
     * Bascule PENDING → AVAILABLE les crédits arrivés à échéance (hors
     * vendeurs suspendus). Appelée par `marketplace:release-earnings`.
     *
     * @return int nombre d'écritures libérées
     */
    public function releaseDue(): int
    {
        $due = VendorLedgerEntry::query()
            ->where('status', 'pending')
            ->where('direction', 'credit')
            ->where('available_at', '<=', now())
            ->whereHas('vendor', fn ($q) => $q->where('status', 'active'))
            ->get();

        $vendorIds = [];

        foreach ($due as $entry) {
            $entry->transitionTo('available', 'settlement_release');
            $vendorIds[$entry->vendor_profile_id] = true;
        }

        VendorProfile::query()->whereIn('id', array_keys($vendorIds))->get()
            ->each(fn (VendorProfile $v) => $this->recompute($v));

        return $due->count();
    }

    /**
     * Reprise du règlement d'une sous-commande remboursée : neutralise le
     * crédit de vente et inscrit un débit de reprise.
     */
    public function reverseForSuborder(MarketplaceSuborder $sub, string $reason): void
    {
        DB::transaction(function () use ($sub, $reason) {
            $sale = VendorLedgerEntry::where('reference', 'sale:'.$sub->id)->first();

            if (! $sale || in_array($sale->status, ['reversed', 'cancelled'], true)) {
                return;
            }

            $wasReleased = $sale->status === 'available';
            $sale->transitionTo('reversed', 'refund', $reason);

            if ($wasReleased) {
                // Les fonds étaient disponibles : on inscrit le débit de reprise
                // (peut créer un report négatif, résorbé par les ventes suivantes).
                VendorLedgerEntry::create([
                    'vendor_profile_id' => $sub->vendor_profile_id,
                    'reference' => 'refund_reversal:'.$sub->id,
                    'direction' => 'debit',
                    'source_type' => 'refund_reversal',
                    'marketplace_suborder_id' => $sub->id,
                    'amount' => $sale->amount,
                    'currency' => $sale->currency,
                    'status' => 'available',
                    'description' => "Reprise remboursement {$sub->suborder_number}",
                ]);
            }

            $sub->update(['settlement_status' => 'reversed']);
        });

        $this->recompute($sub->vendor);
    }

    /** Ajustement manuel par un administrateur (crédit si montant ≥ 0). */
    public function adjust(VendorProfile $vendor, User $admin, float $amount, string $reason): VendorLedgerEntry
    {
        $entry = VendorLedgerEntry::create([
            'vendor_profile_id' => $vendor->id,
            'reference' => 'adjustment:'.Str::uuid(),
            'direction' => $amount >= 0 ? 'credit' : 'debit',
            'source_type' => 'adjustment',
            'amount' => abs($amount),
            'currency' => $vendor->payout_currency,
            'status' => 'available',
            'admin_id' => $admin->getKey(),
            'adjustment_reason' => $reason,
            'description' => 'Ajustement manuel',
        ]);

        $this->recompute($vendor);

        return $entry;
    }

    /** Recalcule le cache `vendor_balances` à partir du registre. */
    public function recompute(VendorProfile $vendor): VendorBalance
    {
        $rows = VendorLedgerEntry::query()->where('vendor_profile_id', $vendor->id)->get();

        $sum = fn ($filter) => (float) $rows->filter($filter)->sum('amount');

        $pending = $sum(fn ($e) => $e->direction === 'credit' && $e->status === 'pending');

        // Payouts en cours de traitement (réservés) — affiché à titre indicatif.
        $reserved = $sum(fn ($e) => $e->direction === 'debit' && $e->source_type === 'payout' && $e->status === 'processing');

        // Retirable maintenant : crédits libérés, moins tous les débits qui ont
        // déjà quitté (ou sont en train de quitter) le solde disponible.
        $available = round(max(0,
            $sum(fn ($e) => $e->direction === 'credit' && $e->status === 'available')
            - $sum(fn ($e) => $e->direction === 'debit'
                && in_array($e->status, ['available', 'processing', 'paid'], true)),
        ), 2);

        return VendorBalance::updateOrCreate(
            ['vendor_profile_id' => $vendor->id],
            [
                'currency' => $vendor->payout_currency,
                'pending_amount' => round($pending, 2),
                'available_amount' => $available,
                'reserved_amount' => round($reserved, 2),
                'lifetime_earned' => $sum(fn ($e) => $e->source_type === 'sale' && $e->status !== 'cancelled' && $e->direction === 'credit'),
                'lifetime_paid' => $sum(fn ($e) => $e->source_type === 'payout' && $e->status === 'paid'),
                'lifetime_reversed' => $sum(fn ($e) => in_array($e->source_type, ['refund_reversal', 'payout_reversal'], true)),
                'updated_at' => now(),
            ],
        );
    }
}
