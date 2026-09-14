<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplacePayoutAccount;
use App\Models\Marketplace\VendorLedgerEntry;
use App\Models\Marketplace\VendorPayout;
use App\Models\Marketplace\VendorProfile;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\MarketplaceSettings;
use App\Support\Sequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * MARKETPLACE — demandes de versement des vendeurs.
 *
 * L'exécution réelle passe (à terme) par un PayoutGateway ; tant qu'aucun
 * prestataire n'est branché, le versement reste `approved` puis est marqué
 * `paid` / `failed` manuellement par un administrateur — les fonds ne sont
 * jamais perdus (même logique que les retraits d'affiliation).
 */
class VendorPayoutService
{
    public function __construct(
        private readonly VendorSettlementService $settlement,
        private readonly Sequence $sequence,
        private readonly AuditLogger $audit,
    ) {}

    public function request(VendorProfile $vendor, ?float $amount, ?string $accountId, User $actor): VendorPayout
    {
        return DB::transaction(function () use ($vendor, $amount, $accountId, $actor) {
            abort_unless($vendor->status === 'active', 403, 'Compte vendeur inactif.');

            $balance = $this->settlement->recompute($vendor);
            $available = (float) $balance->available_amount;
            $amount = $amount === null ? $available : round($amount, 2);

            $min = MarketplaceSettings::minPayoutAmount();

            if ($amount < $min) {
                throw ValidationException::withMessages([
                    'amount' => "Le montant minimum d'un versement est {$min}.",
                ]);
            }
            if ($amount > $available) {
                throw ValidationException::withMessages([
                    'amount' => 'Montant supérieur au solde disponible.',
                ]);
            }

            $account = $accountId
                ? $vendor->payoutAccounts()->findOrFail($accountId)
                : $vendor->payoutAccounts()->where('is_default', true)->first();

            abort_if($account === null, 422, 'Aucun moyen de versement enregistré.');

            $this->assertNoCooldown($account);

            $reference = 'VP-'.str_pad((string) $this->sequence->next('vendor_payout'), 6, '0', STR_PAD_LEFT);

            $payout = VendorPayout::create([
                'reference' => $reference,
                'vendor_profile_id' => $vendor->id,
                'payout_account_id' => $account->id,
                'amount' => $amount,
                'fee' => 0,
                'net_amount' => $amount,
                'currency' => $vendor->payout_currency,
                'method' => $account->method,
                'status' => 'requested',
                'requested_by' => $actor->getKey(),
            ]);

            // Réservation : débit `payout` en `processing`.
            VendorLedgerEntry::create([
                'vendor_profile_id' => $vendor->id,
                'reference' => 'payout:'.$payout->id,
                'direction' => 'debit',
                'source_type' => 'payout',
                'vendor_payout_id' => $payout->id,
                'amount' => $amount,
                'currency' => $vendor->payout_currency,
                'status' => 'processing',
                'description' => "Versement {$reference}",
            ]);

            $this->settlement->recompute($vendor);
            $this->audit->record($actor, 'marketplace.payout.requested', $payout, ['amount' => $amount]);

            return $payout->refresh();
        });
    }

    public function approve(VendorPayout $payout, User $admin): VendorPayout
    {
        abort_unless($payout->status === 'requested', 422);
        $payout->update(['status' => 'approved', 'approved_by' => $admin->getKey()]);
        $this->audit->record($admin, 'marketplace.payout.approved', $payout);

        return $payout;
    }

    public function markPaid(VendorPayout $payout, User $admin, ?string $providerReference = null): VendorPayout
    {
        return DB::transaction(function () use ($payout, $admin, $providerReference) {
            abort_unless(in_array($payout->status, ['approved', 'processing', 'requested'], true), 422);

            $payout->update([
                'status' => 'paid',
                'provider_reference' => $providerReference,
                'processed_at' => now(),
            ]);

            $payout->ledgerEntries()->where('source_type', 'payout')->get()
                ->each(fn (VendorLedgerEntry $e) => $e->transitionTo('paid', 'payout_paid', null, $admin->getKey()));

            $this->settlement->recompute($payout->vendor);
            $this->audit->record($admin, 'marketplace.payout.paid', $payout, ['reference' => $providerReference]);

            return $payout->refresh();
        });
    }

    public function markFailed(VendorPayout $payout, User $admin, string $reason): VendorPayout
    {
        return DB::transaction(function () use ($payout, $admin, $reason) {
            abort_if($payout->status === 'paid', 422, 'Versement déjà payé.');

            $payout->update(['status' => 'failed', 'failure_reason' => $reason, 'processed_at' => now()]);

            // Libère les fonds réservés.
            $payout->ledgerEntries()->where('source_type', 'payout')->get()
                ->each(fn (VendorLedgerEntry $e) => $e->transitionTo('cancelled', 'payout_failed', $reason, $admin->getKey()));

            $this->settlement->recompute($payout->vendor);
            $this->audit->record($admin, 'marketplace.payout.failed', $payout, ['reason' => $reason]);

            return $payout->refresh();
        });
    }

    private function assertNoCooldown(MarketplacePayoutAccount $account): void
    {
        $hours = MarketplaceSettings::payoutAccountCooldownHours();

        if ($account->changed_at && $account->changed_at->diffInHours(now()) < $hours) {
            throw ValidationException::withMessages([
                'account' => "Moyen de versement modifié récemment : nouveau versement possible dans {$hours} h.",
            ]);
        }
    }
}
