<?php

namespace App\Services\Payments;

use App\Models\LedgerEntry;
use App\Models\Order;
use App\Payments\PaymentVerification;
use App\Services\AffiliationService;
use App\Services\DigitalDeliveryService;
use App\Services\OrderRefundService;
use App\Services\ServiceBookingNotifier;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PaymentConfirmationService — logique commune à tous les PSP une fois
 * qu'une transaction a été authentifiée et vérifiée auprès de l'API du
 * prestataire (§26, §27, §66).
 *
 *  - marque la commande comme payée (idempotent, §27) ;
 *  - distribue les commissions d'affiliation (§28-§39) ;
 *  - déclenche la livraison digitale (§9) ;
 *  - ou, pour un remboursement, reprend les commissions (§80).
 */
class PaymentConfirmationService
{
    public function __construct(
        private readonly AffiliationService $affiliation,
        private readonly DigitalDeliveryService $digitalDelivery,
        private readonly OrderRefundService $refunds,
        private readonly StockService $stock,
        private readonly ServiceBookingNotifier $bookingNotifier,
    ) {}

    /**
     * @return bool true si la commande vient réellement d'être marquée
     *              payée (premier événement), false si c'était un doublon.
     */
    public function confirmPaid(Order $order, PaymentVerification $verification): bool
    {
        $justPaid = DB::transaction(function () use ($order, $verification) {
            /** @var Order $order */
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->payment_status === 'paid') {
                Log::info('PaymentConfirmationService: commande déjà payée, événement ignoré (§27).', [
                    'order_id' => $order->id,
                    'reference' => $verification->reference,
                ]);

                return false;
            }

            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'payment_reference' => $verification->reference,
            ]);

            $alreadyHasCommissions = LedgerEntry::query()
                ->where('order_id', $order->id)
                ->where('source_type', 'commission')
                ->exists();

            if (! $alreadyHasCommissions) {
                $this->affiliation->distributeCommissions($order);
            }

            // §11 - les lignes physiques passent de "réservé" à "vendu".
            foreach ($order->items()->where('product_type', 'physical')->get() as $item) {
                $this->stock->markSold($item);
            }

            return true;
        });

        if ($justPaid) {
            // Effets de bord hors transaction financière (§9, §19).
            $freshOrder = $order->fresh('items');
            $this->digitalDelivery->deliverForOrder($freshOrder);
            $this->bookingNotifier->confirmForOrder($freshOrder);
        }

        return $justPaid;
    }

    public function confirmRefunded(Order $order, PaymentVerification $verification): bool
    {
        return $this->refunds->refund(
            $order,
            "Remboursement {$order->payment_provider} (réf. {$verification->reference})"
        );
    }
}
