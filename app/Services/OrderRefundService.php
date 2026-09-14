<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OrderRefundService — centralise les effets de bord d'un remboursement
 * de commande (§80).
 *
 *  - Produit digital affilié : la commission correspondante est
 *    annulée (si en attente) ou reprise (si disponible/payée).
 *  - Produit physique : remise en stock.
 *  - Service : libération du créneau (§19).
 *
 * Idempotent : une commande déjà remboursée n'est pas retraitée.
 */
class OrderRefundService
{
    public function __construct(
        private readonly AffiliationService $affiliation,
        private readonly StockService $stock,
        private readonly ServiceAvailabilityService $availability,
        private readonly CouponService $coupons,
        private readonly ServiceBookingNotifier $bookingNotifier,
    ) {}

    public function refund(Order $order, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($order, $reason) {
            /** @var Order $order */
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->payment_status === 'refunded') {
                Log::info('OrderRefundService: commande déjà remboursée, ignorée.', ['order_id' => $order->id]);

                return false;
            }

            $order->update([
                'payment_status' => 'refunded',
                'status' => 'refunded',
                'notes' => trim(($order->notes ? $order->notes."\n" : '').'Remboursement'.($reason ? " : {$reason}" : '')),
            ]);

            // §80 — reprise/annulation des commissions d'affiliation.
            $this->affiliation->reverseCommissionsForOrder($order);

            // §80 — remise en stock des lignes physiques.
            foreach ($order->items()->where('product_type', 'physical')->get() as $item) {
                $this->stock->restockFromReturn($item);
            }

            // §80/§19 — libération des créneaux de service réservés
            // + notification d'annulation au client.
            foreach ($order->items()->where('product_type', 'service')->get() as $item) {
                $this->availability->releaseSlot($item);
                $this->bookingNotifier->cancelled($item);
            }

            // §79/§80 — le coupon utilisé est rendu disponible.
            if ($order->coupon) {
                $this->coupons->release($order->coupon);
            }

            Log::warning('OrderRefundService: commande remboursée.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'reason' => $reason,
            ]);

            return true;
        });
    }
}
