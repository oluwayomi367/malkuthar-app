<?php

namespace App\Http\Controllers;

use App\Exceptions\WithdrawalException;
use App\Http\Requests\WithdrawalRequest;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;

/**
 * WithdrawalController — retrait autonome des affiliés (Phase 6 & 7, §50).
 * Contrôleur volontairement fin : toute la logique de débit du wallet et
 * de rollback vit dans WithdrawalService.
 */
class WithdrawalController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawalService,
    ) {
    }

    /**
     * POST /affiliate/withdrawals
     *
     * L'affilié saisit un montant et son numéro Mobile Money. Le système
     * vérifie que le montant est ≤ au solde disponible du wallet, crée
     * une écriture de débit dans le ledger (le solde est recalculé
     * instantanément via le wallet en cache) et place la demande au
     * statut PROCESSING, en attente de confirmation par l'opérateur.
     */
    public function requestWithdrawal(WithdrawalRequest $request): JsonResponse
    {
        try {
            $withdrawal = $this->withdrawalService->requestWithdrawal(
                affiliate: $request->user(),
                data: $request->toWithdrawalData(),
            );
        } catch (WithdrawalException $e) {
            // Solde insuffisant ou montant sous le minimum configuré :
            // erreur métier prévisible, pas une panne serveur.
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Votre demande de retrait a été enregistrée et est en cours de traitement.',
            'data' => [
                'withdrawal_id' => $withdrawal->id,
                'reference' => $withdrawal->reference,
                'amount' => $withdrawal->amount,
                'currency' => $withdrawal->currency,
                'method' => $withdrawal->method,
                'status' => $withdrawal->status,
                'requested_at' => $withdrawal->requested_at,
            ],
        ], 201);
    }
}
