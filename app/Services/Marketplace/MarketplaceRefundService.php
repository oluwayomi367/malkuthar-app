<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplaceSuborder;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * MARKETPLACE — remboursement / annulation d'une sous-commande PAYÉE.
 *
 * Reprend le règlement du vendeur, remet le stock, et — si toutes les
 * sous-commandes d'une commande sont annulées — passe la commande en
 * « remboursée ».
 */
class MarketplaceRefundService
{
    public function __construct(
        private readonly VendorSettlementService $settlement,
        private readonly AuditLogger $audit,
    ) {}

    public function refundSuborder(MarketplaceSuborder $sub, string $reason, ?User $admin = null): MarketplaceSuborder
    {
        DB::transaction(function () use ($sub, $reason, $admin) {
            abort_if($sub->settlement_status === 'reversed', 422, 'Sous-commande déjà reprise.');

            $sub->loadMissing('items.offer.physical', 'items.offer.training', 'items.variant', 'order');

            // Remise en stock / places.
            foreach ($sub->items as $item) {
                $offer = $item->offer;
                if (! $offer) {
                    continue;
                }
                $offer->decrement('sales_count', min($item->quantity, $offer->sales_count));

                if ($offer->type === 'physical') {
                    if ($item->variant) {
                        $item->variant->increment('stock', $item->quantity);
                    } elseif ($offer->physical) {
                        $offer->physical->increment('stock', $item->quantity);
                    }
                }
                if ($offer->type === 'training' && $offer->training) {
                    $offer->training->decrement('seats_taken', min($item->quantity, $offer->training->seats_taken));
                }
            }

            $sub->update(['fulfilment_status' => 'cancelled']);

            // Reprise du règlement vendeur.
            $this->settlement->reverseForSuborder($sub, $reason);

            // Commande entièrement annulée ?
            $order = $sub->order;
            $remaining = $order->suborders()->where('settlement_status', '!=', 'reversed')->exists();
            $order->update([
                'status' => $remaining ? 'processing' : 'refunded',
                'payment_status' => $remaining ? $order->payment_status : 'refunded',
            ]);

            $this->audit->record($admin, 'marketplace.suborder.refunded', $sub, ['reason' => $reason]);
        });

        return $sub->refresh();
    }
}
