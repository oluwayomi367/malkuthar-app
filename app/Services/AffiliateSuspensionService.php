<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * §63 — suspension d'un affilié. La suspension DÉFINIT explicitement le
 * traitement de ses commissions en attente, de ses commissions disponibles
 * et de ses retraits en cours. Aucune donnée financière n'est supprimée :
 * tout passe par des transitions de statut et des écritures de ledger.
 */
class AffiliateSuspensionService
{
    /** Motifs de suspension (§63). */
    public const CATEGORIES = [
        'fraud' => 'Fraude',
        'suspicious_activity' => 'Activité suspecte',
        'terms_violation' => 'Violation des conditions',
        'manipulation' => 'Manipulation',
        'fraudulent_accounts' => 'Comptes frauduleux',
    ];

    /** Traitements possibles, par « poche ». */
    public const TREATMENTS = [
        'pending' => ['freeze' => 'Geler (rester en attente)', 'cancel' => 'Annuler'],
        'available' => ['freeze' => 'Geler (retrait bloqué)', 'clawback' => 'Reprendre (débit tracé)'],
        'withdrawals' => ['hold' => 'Laisser se terminer', 'rollback' => 'Annuler et recréditer'],
    ];

    public function __construct(
        private readonly WalletAdjustmentService $walletAdjustments,
        private readonly WithdrawalService $withdrawals,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{pending: string, available: string, withdrawals: string}  $treatment
     * @return array{pending_commissions: int, pending_treatment: string, available_amount: float, available_treatment: string, processing_withdrawals: int, withdrawals_treatment: string}
     */
    public function suspend(
        User $affiliate,
        User $admin,
        string $category,
        string $reason,
        array $treatment,
        ?Request $request = null,
    ): array {
        return DB::transaction(function () use ($affiliate, $admin, $category, $reason, $treatment, $request) {
            $affiliate->forceFill([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspension_category' => $category,
                'suspension_reason' => $reason,
            ])->save();

            // --- Commissions EN ATTENTE ---
            $pending = LedgerEntry::query()
                ->where('user_id', $affiliate->id)
                ->where('source_type', 'commission')
                ->where('status', 'pending')
                ->get();

            if ($treatment['pending'] === 'cancel') {
                foreach ($pending as $entry) {
                    // §41 — chaque transition consignée (contexte suspension).
                    $entry->transitionTo('cancelled', 'suspension', $reason, $admin->id);
                }
                $this->recomputePendingCache($affiliate);
            }
            // 'freeze' : rien à faire — releaseDueCommissions ignore les suspendus.

            // --- Commissions DISPONIBLES ---
            $availableAmount = (float) LedgerEntry::query()
                ->where('user_id', $affiliate->id)
                ->where('source_type', 'commission')
                ->where('status', 'available')
                ->sum('amount');

            if ($treatment['available'] === 'clawback' && $availableAmount > 0.0) {
                $this->walletAdjustments->adjust(
                    $admin,
                    $affiliate,
                    $availableAmount,
                    'debit',
                    "Reprise du solde disponible — suspension ({$category}) : {$reason}",
                );
            }
            // 'freeze' : le solde reste, mais tout retrait est déjà bloqué pour un compte suspendu.

            // --- Retraits EN COURS ---
            $processing = Withdrawal::query()
                ->where('user_id', $affiliate->id)
                ->where('status', 'processing')
                ->get();

            if ($treatment['withdrawals'] === 'rollback') {
                foreach ($processing as $withdrawal) {
                    $this->withdrawals->rollbackFailedWithdrawal($withdrawal, "Suspension du compte ({$category}) : {$reason}");
                }
            }
            // 'hold' : laissés en l'état, ils se termineront (payé / échoué) normalement.

            $affected = [
                'pending_commissions' => $pending->count(),
                'pending_treatment' => $treatment['pending'],
                'available_amount' => $availableAmount,
                'available_treatment' => $treatment['available'],
                'processing_withdrawals' => $processing->count(),
                'withdrawals_treatment' => $treatment['withdrawals'],
            ];

            $this->audit->record(
                $admin,
                'affiliate.suspended',
                $affiliate,
                ['category' => $category, 'reason' => $reason, 'treatment' => $treatment, 'affected' => $affected],
                "Suspension de l'affilié {$affiliate->affiliate_id} ({$category}) : {$reason}",
                $request,
            );

            return $affected;
        });
    }

    public function reactivate(User $affiliate, User $admin, ?Request $request = null): void
    {
        $previousReason = $affiliate->suspension_reason;

        $affiliate->forceFill([
            'status' => 'active',
            'suspended_at' => null,
            'suspension_category' => null,
            'suspension_reason' => null,
        ])->save();

        $this->audit->record(
            $admin,
            'affiliate.reactivated',
            $affiliate,
            ['previous_reason' => $previousReason],
            "Réactivation de l'affilié {$affiliate->affiliate_id}",
            $request,
        );
    }

    private function recomputePendingCache(User $affiliate): void
    {
        $wallet = $affiliate->wallet;
        if (! $wallet) {
            return;
        }

        $pending = (float) LedgerEntry::query()
            ->where('user_id', $affiliate->id)
            ->where('source_type', 'commission')
            ->where('status', 'pending')
            ->sum('amount');

        $wallet->update(['balance_pending' => $pending]);
    }
}
