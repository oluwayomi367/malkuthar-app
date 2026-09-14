<?php

namespace App\Services;

use App\Exceptions\WithdrawalException;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Notifications\WithdrawalFailedNotification;
use App\Notifications\WithdrawalPaidNotification;
use App\Notifications\WithdrawalRequestedNotification;
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
    public function __construct(private readonly WithdrawalFeeCalculator $fees) {}

    /**
     * Crée la demande de retrait, débite immédiatement le solde
     * disponible via une écriture de ledger, et place la demande au
     * statut PROCESSING (le virement effectif est ensuite pris en charge
     * par le prestataire de payout, hors de cette méthode).
     *
     * @param  array{amount: float, method: string, account_details: array, security_verified?: bool, verification_method?: string}  $data
     *
     * @throws WithdrawalException si le solde est insuffisant, le montant
     *                             est hors bornes configurées, ou une
     *                             modification récente du moyen de
     *                             retrait impose un délai de sécurité (§56).
     */
    public function requestWithdrawal(User $affiliate, array $data): Withdrawal
    {
        $withdrawal = DB::transaction(function () use ($affiliate, $data) {
            // §62 - un affilié suspendu/banni ne peut pas initier de
            // nouveau retrait (les commissions/retraits déjà en cours ne
            // sont pas touchés, traités au cas par cas par MALKUTHAR).
            if ($affiliate->status !== 'active') {
                throw WithdrawalException::accountNotActive($affiliate->status);
            }

            $amount = round((float) $data['amount'], 2);

            // §51 - minimum et plafond éventuel, configurables depuis l'administration.
            $minimum = (float) config('malkuthar.withdrawals.minimum_amount', 1000);
            $maximum = config('malkuthar.withdrawals.maximum_amount');

            if ($amount < $minimum) {
                throw WithdrawalException::belowMinimumAmount($amount, $minimum);
            }

            if ($maximum !== null && $amount > (float) $maximum) {
                throw WithdrawalException::aboveMaximumAmount($amount, (float) $maximum);
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

            // §56 - délai de sécurité après une modification récente du
            // moyen de retrait (protection contre un retrait frauduleux
            // immédiatement après un changement de compte).
            $cooldownHours = (int) config('malkuthar.withdrawals.cooldown_after_account_change_hours', 24);
            if ($cooldownHours > 0 && $wallet->withdrawal_method_changed_at
                && $wallet->withdrawal_method_changed_at->diffInHours(now(), absolute: true) < $cooldownHours) {
                throw WithdrawalException::cooldownActive(
                    $wallet->withdrawal_method_changed_at->addHours($cooldownHours)
                );
            }

            $fee = $this->fees->compute($amount);
            $netAmount = round($amount - $fee, 2);

            $withdrawal = Withdrawal::create([
                'user_id' => $affiliate->id,
                'wallet_id' => $wallet->id,
                'reference' => $this->generateReference(),
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'currency' => $wallet->currency,
                'method' => $data['method'],
                'country' => $affiliate->country,
                'account_details' => $data['account_details'],
                'status' => 'processing',
                // §55 - vrai uniquement si l'appelant a déjà fait
                // vérifier une authentification renforcée (OTP, etc.).
                'security_verified' => (bool) ($data['security_verified'] ?? false),
                'verification_method' => $data['verification_method'] ?? null,
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
                'description' => sprintf('Retrait %s #%s', $data['method'], $withdrawal->reference),
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

        // §59 - notification hors transaction.
        $affiliate->notify(new WithdrawalRequestedNotification($withdrawal));

        return $withdrawal;
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
        $processed = DB::transaction(function () use ($withdrawal, $failureReason) {
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

                return false;
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
                ->get()
                ->each(fn (LedgerEntry $e) => $e->transitionTo('failed', 'withdrawal_rollback', $failureReason));

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

            return true;
        });

        if ($processed) {
            // §59 - notification hors transaction.
            $withdrawal->fresh()->user?->notify(new WithdrawalFailedNotification($withdrawal->fresh()));
        }
    }

    /**
     * Confirme un retrait effectivement payé par l'opérateur Mobile
     * Money. Fait pendant symétrique du rollback, fourni pour compléter
     * le cycle de vie (§41 - statut PAID).
     */
    public function markWithdrawalAsPaid(Withdrawal $withdrawal, ?string $providerReference = null): void
    {
        $processed = DB::transaction(function () use ($withdrawal, $providerReference) {
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

                return false;
            }

            LedgerEntry::query()
                ->where('withdrawal_id', $withdrawal->id)
                ->where('source_type', 'withdrawal')
                ->where('direction', 'debit')
                ->get()
                ->each(fn (LedgerEntry $e) => $e->transitionTo('paid', 'withdrawal_paid', $providerReference));

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

            return true;
        });

        if ($processed) {
            // §59 - notification hors transaction.
            $withdrawal->fresh()->user?->notify(new WithdrawalPaidNotification($withdrawal->fresh()));
        }
    }

    private function generateReference(): string
    {
        return 'WD-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
    }
}
