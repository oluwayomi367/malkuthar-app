<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Payments\PaymentVerification;
use App\Services\OrderRefundService;
use App\Services\Payments\PaymentConfirmationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;

/**
 * Aides de développement LOCAL uniquement (jamais chargées hors
 * environnement local, cf. routes/web.php). Permet de simuler la
 * confirmation d'un paiement sans passer par un vrai webhook PSP.
 */
class DevController extends Controller
{
    public function confirmPayment(Order $order, PaymentConfirmationService $confirmation): RedirectResponse
    {
        abort_unless(App::environment('local'), 404);

        $confirmation->confirmPaid(
            $order,
            new PaymentVerification(
                status: PaymentVerification::PAID,
                reference: $order->payment_reference ?: 'DEV-'.$order->order_number,
            )
        );

        return redirect()
            ->route('checkout.return', $order)
            ->with('status', 'Paiement simulé (mode développement).');
    }

    public function refund(Order $order, OrderRefundService $refunds): RedirectResponse
    {
        abort_unless(App::environment('local'), 404);

        $refunds->refund($order, 'Remboursement simulé (mode développement).');

        return redirect()->route('checkout.return', $order);
    }
}
