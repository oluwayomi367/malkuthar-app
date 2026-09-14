<?php

namespace App\Http\Controllers;

use App\Exceptions\WithdrawalException;
use App\Http\Requests\WithdrawalMethodChangeRequest;
use App\Mail\OtpCodeMail;
use App\Services\AffiliateWithdrawalMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * §56 — Modification du moyen de retrait, protégée par OTP :
 *   Nouveau numéro -> OTP -> Confirmation -> Enregistrement.
 */
class AffiliateWithdrawalMethodController extends Controller
{
    public function __construct(private readonly AffiliateWithdrawalMethodService $service) {}

    /**
     * POST /affiliate/withdrawal-method — étape 1 : envoie le code.
     */
    public function store(WithdrawalMethodChangeRequest $request): JsonResponse
    {
        $code = $this->service->requestChange($request->user(), [
            'method' => $request->validated()['method'],
            'account_details' => $request->toAccountDetails(),
        ]);

        Mail::to($request->user()->email)->queue(new OtpCodeMail($code, 'modification du moyen de retrait'));

        return response()->json([
            'message' => 'Un code de vérification a été envoyé à votre adresse email.',
        ]);
    }

    /**
     * POST /affiliate/withdrawal-method/confirm — étape 2 : vérifie le code.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        try {
            $wallet = $this->service->confirmChange($request->user(), $request->input('code'));
        } catch (WithdrawalException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Votre moyen de retrait a été mis à jour.',
            'data' => [
                'method' => $wallet->default_withdrawal_method,
                'changed_at' => $wallet->withdrawal_method_changed_at,
            ],
        ]);
    }
}
