<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplaceOrderItem;
use App\Models\Marketplace\MarketplaceSuborder;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * MARKETPLACE — traitement d'une sous-commande par le vendeur.
 */
class MarketplaceFulfilmentService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function ship(MarketplaceSuborder $sub, ?string $carrier, ?string $tracking, User $actor): MarketplaceSuborder
    {
        $this->assertPaid($sub);

        $sub->update([
            'fulfilment_status' => 'shipped',
            'shipping_carrier' => $carrier,
            'tracking_number' => $tracking,
        ]);
        $sub->items()->update(['fulfilment_status' => 'shipped']);

        $this->audit->record($actor, 'marketplace.suborder.shipped', $sub, compact('carrier', 'tracking'));

        return $sub->refresh();
    }

    public function advance(MarketplaceSuborder $sub, string $to, User $actor): MarketplaceSuborder
    {
        $allowed = ['processing', 'shipped', 'delivered', 'completed'];
        abort_unless(in_array($to, $allowed, true), 422);
        $this->assertPaid($sub);

        $sub->update(['fulfilment_status' => $to]);
        $sub->items()->update(['fulfilment_status' => $to]);

        if ($to === 'completed') {
            $this->maybeCompleteOrder($sub);
        }

        $this->audit->record($actor, 'marketplace.suborder.fulfilment', $sub, ['to' => $to]);

        return $sub->refresh();
    }

    public function confirmBooking(MarketplaceOrderItem $item, User $actor): MarketplaceOrderItem
    {
        abort_unless($item->type === 'service', 422, 'Ligne non réservable.');
        $item->update(['fulfilment_status' => 'processing']);
        $this->audit->record($actor, 'marketplace.booking.confirmed', $item);

        return $item->refresh();
    }

    private function assertPaid(MarketplaceSuborder $sub): void
    {
        if ($sub->order->payment_status !== 'paid') {
            throw ValidationException::withMessages(['suborder' => 'Commande pas encore payée.']);
        }
        if (in_array($sub->fulfilment_status, ['cancelled'], true)) {
            throw ValidationException::withMessages(['suborder' => 'Sous-commande annulée.']);
        }
    }

    private function maybeCompleteOrder(MarketplaceSuborder $sub): void
    {
        $order = $sub->order;
        $pending = $order->suborders()
            ->whereNotIn('fulfilment_status', ['completed', 'cancelled'])
            ->exists();

        if (! $pending) {
            $order->update(['status' => 'completed']);
        }
    }
}
