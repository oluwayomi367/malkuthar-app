<?php

namespace App\Http\Controllers;

use App\Exceptions\WithdrawalException;
use App\Http\Requests\WithdrawalRequest;
use App\Services\Payments\ProcessWithdrawalPayout;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * WithdrawalController — retrait autonome des affiliés (§50-§58).
 * Contrôleur volontairement fin : toute la logique de débit du wallet et
 * de rollback vit dans WithdrawalService ; l'envoi effectif des fonds
 * vit dans ProcessWithdrawalPayout (§54).
 */
class WithdrawalController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawalService,
        private readonly ProcessWithdrawalPayout $payoutProcessor,
    ) {}

    /**
     * POST /affiliate/withdrawals
     */
    public function requestWithdrawal(WithdrawalRequest $request): JsonResponse
    {
        try {
            $withdrawal = $this->withdrawalService->requestWithdrawal(
                affiliate: $request->user(),
                data: $request->toWithdrawalData(),
            );
        } catch (WithdrawalException $e) {
            // Solde insuffisant, montant hors bornes, ou cooldown de
            // sécurité actif (§56) : erreur métier prévisible.
            return response()->json(['message' => $e->getMessage()], 422);
        }

        try {
            // §54 - tentative d'automatisation immédiate. Si elle échoue,
            // les fonds restent réservés (ledger) : rien n'est perdu, un
            // administrateur peut traiter le retrait manuellement.
            $this->payoutProcessor->process($withdrawal);
        } catch (Throwable $e) {
            report($e);
        }

        $withdrawal->refresh();

        return response()->json([
            'message' => match ($withdrawal->status) {
                'paid' => 'Votre retrait a été effectué.',
                'failed' => 'Votre retrait n\'a pas pu être effectué. Les fonds ont été recrédités sur votre solde.',
                default => 'Votre demande de retrait a été enregistrée et est en cours de traitement.',
            },
            'data' => [
                'withdrawal_id' => $withdrawal->id,
                'reference' => $withdrawal->reference,
                'amount' => $withdrawal->amount,
                'fee' => $withdrawal->fee,
                'net_amount' => $withdrawal->net_amount,
                'currency' => $withdrawal->currency,
                'method' => $withdrawal->method,
                'status' => $withdrawal->status,
                'requested_at' => $withdrawal->requested_at,
            ],
        ], 201);
    }
}
