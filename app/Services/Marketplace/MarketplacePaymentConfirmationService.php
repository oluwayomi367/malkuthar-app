<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplaceOrder;
use App\Models\Marketplace\MarketplaceSuborder;
use App\Notifications\Marketplace\MarketplaceOrderPaid;
use App\Notifications\Marketplace\MarketplaceVendorSale;
use App\Support\MarketplaceSettings;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MARKETPLACE — confirmation de paiement d'une commande de la place de
 * marché (idempotent, §27).
 *
 *  - passe la commande et ses sous-commandes en « payé » ;
 *  - déstocke les produits physiques (réservé → vendu) ;
 *  - ouvre la fenêtre de règlement de chaque sous-commande
 *    (`available_at = paid_at + hold_days`) ;
 *  - notifie l'acheteur et chaque vendeur.
 *
 * N'appelle JAMAIS AffiliationService : pas de commission 3 niveaux ici.
 * Le crédit du registre vendeur est posé à la phase 6 (VendorSettlementService).
 */
class MarketplacePaymentConfirmationService
{
    public function confirmPaid(MarketplaceOrder $order, ?string $reference = null): bool
    {
        $justPaid = DB::transaction(function () use ($order, $reference) {
            /** @var MarketplaceOrder $order */
            $order = MarketplaceOrder::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->payment_status === 'paid') {
                Log::info('MarketplacePaymentConfirmation: déjà payée, ignorée (§27).', ['order' => $order->id]);

                return false;
            }

            $paidAt = now();
            $holdDays = MarketplaceSettings::settlementHoldDays();

            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'paid_at' => $paidAt,
                'payment_reference' => $reference ?? $order->payment_reference,
            ]);

            $order->loadMissing('suborders.items.offer.physical', 'suborders.items.variant');

            $settlement = App::make(VendorSettlementService::class);

            foreach ($order->suborders as $sub) {
                $sub->setRelation('order', $order);
                $sub->update([
                    'settlement_status' => 'pending',
                    'available_at' => $paidAt->copy()->addDays($holdDays),
                    'fulfilment_status' => 'processing',
                ]);

                $this->fulfilStock($sub);

                // Crédit du registre vendeur (net après commission).
                $settlement->recordSale($sub);
            }

            return true;
        });

        if ($justPaid) {
            $fresh = $order->fresh('suborders.vendor.user');
            $fresh->notify(new MarketplaceOrderPaid($fresh));

            foreach ($fresh->suborders as $sub) {
                $sub->vendor->user?->notify(new MarketplaceVendorSale($sub));
            }
        }

        return $justPaid;
    }

    /** Réservé → vendu pour les lignes physiques ; compteurs de ventes. */
    private function fulfilStock(MarketplaceSuborder $sub): void
    {
        foreach ($sub->items as $item) {
            $offer = $item->offer;

            if (! $offer) {
                continue;
            }

            $offer->increment('sales_count', $item->quantity);

            if ($offer->type === 'physical') {
                if ($item->variant) {
                    $item->variant->decrement('stock', $item->quantity);
                    $item->variant->decrement('stock_reserved', min($item->quantity, $item->variant->stock_reserved));
                } elseif ($offer->physical) {
                    $offer->physical->decrement('stock', $item->quantity);
                    $offer->physical->decrement('stock_reserved', min($item->quantity, $offer->physical->stock_reserved));
                }
            }

            if ($offer->type === 'training' && $offer->training) {
                $offer->training->increment('seats_taken', $item->quantity);
            }
        }
    }

    /**
     * Annulation d'une commande non payée : libère le stock réservé.
     */
    public function cancelUnpaid(MarketplaceOrder $order, string $reason): void
    {
        DB::transaction(function () use ($order) {
            abort_if($order->payment_status === 'paid', 422, 'Commande déjà payée.');

            $order->loadMissing('suborders.items.offer.physical', 'suborders.items.variant');

            foreach ($order->suborders as $sub) {
                foreach ($sub->items as $item) {
                    if ($item->offer?->type !== 'physical') {
                        continue;
                    }
                    if ($item->variant) {
                        $item->variant->decrement('stock_reserved', min($item->quantity, $item->variant->stock_reserved));
                    } elseif ($item->offer->physical) {
                        $item->offer->physical->decrement('stock_reserved', min($item->quantity, $item->offer->physical->stock_reserved));
                    }
                }
                $sub->update(['settlement_status' => 'cancelled', 'fulfilment_status' => 'cancelled']);
            }

            $order->update(['status' => 'cancelled', 'payment_status' => 'failed']);
        });
    }
}
