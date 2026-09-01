<?php

namespace App\Services;

use App\Exceptions\WithdrawalException;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WithdrawalService — retrait autonome des affiliés (§50-§58 du cahier
 * des charges).
 *
 * RÈGLE CENTRALE (§48-§49) : le wallet n'est JAMAIS débité/recrédité par
 * une simple écriture de colonne. Chaque mouvement passe par une
 * LedgerEntry immuable ; le solde en cache (balance_available) n'est mis
 * à jour qu'en miroir d'une écriture réellement créée.
 */
class WithdrawalService
{
    /**
     * Crée la demande de retrait, débite immédiatement le solde
     * disponible via une écriture de ledger, et place la demande au
     * statut PROCESSING (le virement effectif est ensuite pris en charge
     * par le prestataire de payout, hors de cette méthode).
     *
     * @param  array{amount: float, method: string, account_details: array}  $data
     *
     * @throws WithdrawalException si le solde est insuffisant ou le
     *                              montant est sous le minimum configuré.
     */
    public function requestWithdrawal(User $affiliate, array $data): Withdrawal
    {
        return DB::transaction(function () use ($affiliate, $data) {
            $amount = round((float) $data['amount'], 2);

            // §51 - minimum de retrait configurable depuis l'administration.
            $minimum = (float) config('malkuthar.withdrawals.minimum_amount', 1000);

            if ($amount < $minimum) {
                throw WithdrawalException::belowMinimumAmount($amount, $minimum);
            }

            // Verrou pessimiste sur le wallet : empêche deux demandes de
            // retrait concurrentes de dépasser le solde disponible
            // (protection anti "double-spend").
            $wallet = Wallet::query()
                ->where('user_id', $affiliate->id)
                ->lockForUpdate()
                ->first();

            $availableBalance = $wallet ? (float) $wallet->balance_available : 0.0;

            // §50 - le montant demandé ne doit jamais dépasser le solde
            // disponible (solde_disponible) du wallet.
            if (! $wallet || $amount > $availableBalance) {
                throw WithdrawalException::insufficientBalance($amount, $availableBalance);
            }

            $withdrawal = Withdrawal::create([
                'user_id' => $affiliate->id,
                'wallet_id' => $wallet->id,
                'reference' => $this->generateReference(),
                'amount' => $amount,
                'fee' => 0, // TODO §57 : brancher un FeeCalculationService (frais fixes/%, selon pays/méthode)
                'net_amount' => $amount,
                'currency' => $wallet->currency,
                'method' => $data['method'],
                'country' => $affiliate->country,
                'account_details' => $data['account_details'],
                'status' => 'processing',
                // TODO §55 : n'autoriser le passage en PROCESSING qu'après
                // vérification OTP/authentification renforcée. Laissé à
                // false ici tant que ce flux n'est pas implémenté.
                'security_verified' => false,
                'requested_at' => now(),
            ]);

            // §48 - écriture de débit dans le ledger : c'est ELLE qui fait
            // foi, pas une simple décrémentation de colonne.
            $ledgerEntry = LedgerEntry::create([
                'wallet_id' => $wallet->id,
                'user_id' => $affiliate->id,
                'reference' => sprintf('WD-%s-DEBIT', $withdrawal->reference),
                'direction' => 'debit',
                'source_type' => 'withdrawal',
                'source_id' => $withdrawal->id,
                'withdrawal_id' => $withdrawal->id,
                'amount' => $amount,
                'currency' => $wallet->currency,
                'status' => 'processing',
                'description' => sprintf('Retrait Mobile Money #%s', $withdrawal->reference),
            ]);

            // Le solde disponible est immédiatement réservé (§48 : le
            // cache est mis à jour EN MIROIR de l'écriture de ledger qui
            // vient d'être créée, jamais indépendamment d'elle).
            $wallet->decrement('balance_available', $amount);

            Log::info('WithdrawalService: demande de retrait créée, solde débité.', [
                'withdrawal_id' => $withdrawal->id,
                'ledger_entry_id' => $ledgerEntry->id,
                'user_id' => $affiliate->id,
                'amount' => $amount,
            ]);

            return $withdrawal->fresh();
        });
    }

    /**
     * ROLLBACK — à appeler lorsque le prestataire de payout (Mobile
     * Money) confirme l'ÉCHEC du virement (§10 - Test 10 : "l'argent
     * n'est pas définitivement perdu"). Réinsère le montant dans le
     * wallet via une NOUVELLE écriture de crédit (jamais en modifiant
     * l'écriture de débit d'origine ni en réécrivant balance_available
     * directement — §49).
     *
     * Idempotent : si le retrait a déjà été finalisé (paid/failed/
     * cancelled), l'appel est ignoré sans effet de bord.
     */
    public function rollbackFailedWithdrawal(Withdrawal $withdrawal, ?string $failureReason = null): void
    {
        DB::transaction(function () use ($withdrawal, $failureReason) {
            /** @var Withdrawal $withdrawal */
            $withdrawal = Withdrawal::query()
                ->whereKey($withdrawal->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($withdrawal->status !== 'processing') {
                // Déjà payé, déjà échoué, ou annulé : ne rien refaire
                // (protection contre un double webhook de l'opérateur).
                Log::info('WithdrawalService: rollback ignoré, retrait déjà finalisé.', [
                    'withdrawal_id' => $withdrawal->id,
                    'status' => $withdrawal->status,
                ]);

                return;
            }

            $wallet = Wallet::query()
                ->whereKey($withdrawal->wallet_id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. On marque l'écriture de débit d'origine comme échouée
            //    (elle reste en base pour l'audit, mais n'est plus
            //    comptée comme un débit "effectif" dans les recalculs de
            //    solde — §67, journal d'audit).
            LedgerEntry::query()
                ->where('withdrawal_id', $withdrawal->id)
                ->where('source_type', 'withdrawal')
                ->where('direction', 'debit')
                ->update(['status' => 'failed']);

            // 2. On crée une ÉCRITURE DE CRÉDIT distincte pour le
            //    remboursement — jamais une correction en place (§49).
            $refundEntry = LedgerEntry::create([
                'wallet_id' => $wallet->id,
                'user_id' => $withdrawal->user_id,
                'reference' => sprintf('WD-%s-REFUND', $withdrawal->reference),
                'direction' => 'credit',
                'source_type' => 'withdrawal_failed_refund',
                'source_id' => $withdrawal->id,
                'withdrawal_id' => $withdrawal->id,
                'amount' => $withdrawal->amount,
                'currency' => $withdrawal->currency,
                'status' => 'available', // immédiatement disponible pour une nouvelle tentative
                'description' => sprintf(
                    'Remboursement suite à échec du retrait #%s%s',
                    $withdrawal->reference,
                    $failureReason ? " ({$failureReason})" : ''
                ),
            ]);

            $wallet->increment('balance_available', $withdrawal->amount);

            $withdrawal->update([
                'status' => 'failed',
                'failure_reason' => $failureReason,
                'processed_at' => now(),
            ]);

            Log::warning('WithdrawalService: retrait échoué, fonds réinsérés dans le wallet.', [
                'withdrawal_id' => $withdrawal->id,
                'refund_ledger_entry_id' => $refundEntry->id,
                'amount' => $withdrawal->amount,
                'reason' => $failureReason,
            ]);
        });
    }

    /**
     * Confirme un retrait effectivement payé par l'opérateur Mobile
     * Money. Fait pendant symétrique du rollback, fourni pour compléter
     * le cycle de vie (§41 - statut PAID).
     */
    public function markWithdrawalAsPaid(Withdrawal $withdrawal, ?string $providerReference = null): void
    {
        DB::transaction(function () use ($withdrawal, $providerReference) {
            /** @var Withdrawal $withdrawal */
            $withdrawal = Withdrawal::query()
                ->whereKey($withdrawal->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($withdrawal->status !== 'processing') {
                Log::info('WithdrawalService: confirmation ignorée, retrait déjà finalisé.', [
                    'withdrawal_id' => $withdrawal->id,
                    'status' => $withdrawal->status,
                ]);

                return;
            }

            LedgerEntry::query()
                ->where('withdrawal_id', $withdrawal->id)
                ->where('source_type', 'withdrawal')
                ->where('direction', 'debit')
                ->update(['status' => 'paid']);

            $wallet = Wallet::query()->whereKey($withdrawal->wallet_id)->lockForUpdate()->first();
            $wallet?->increment('total_withdrawn', $withdrawal->amount);

            $withdrawal->update([
                'status' => 'paid',
                'provider_reference' => $providerReference,
                'processed_at' => now(),
            ]);

            Log::info('WithdrawalService: retrait confirmé payé.', [
                'withdrawal_id' => $withdrawal->id,
            ]);
        });
    }

    private function generateReference(): string
    {
        return 'WD-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
    }
}
