<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\PaymentConfirmationService;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\View\View;
use Throwable;

/**
 * §26 — Page de retour après paiement. On ne se fie JAMAIS au seul fait
 * que le client soit revenu sur cette page : on ré-interroge l'API du PSP
 * pour connaître le statut réel, et le webhook reste la source de vérité
 * asynchrone.
 */
class CheckoutReturnController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly PaymentConfirmationService $confirmation,
    ) {}

    public function __invoke(Order $order): View
    {
        $status = $order->payment_status;

        // Paiement à la livraison : aucun PSP à ré-interroger, la commande
        // reste « en attente » jusqu'à l'encaissement par un administrateur.
        if ($order->isCashOnDelivery()) {
            return view('storefront.checkout.return', [
                'order' => $order,
                'status' => $status,
            ]);
        }

        if ($order->payment_status !== 'paid' && $order->payment_reference && $order->payment_provider) {
            try {
                $verification = $this->gateways->for($order->payment_provider)
                    ->verifyTransaction($order->payment_reference);

                if ($verification->isPaid()) {
                    $this->confirmation->confirmPaid($order, $verification);
                    $status = 'paid';
                } else {
                    $status = $verification->status;
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return view('storefront.checkout.return', [
            'order' => $order->fresh(),
            'status' => $status,
        ]);
    }
}
