<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\ServiceBookingNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * §19 — orchestre les notifications de rendez-vous (confirmation immédiate,
 * rappel, modification, annulation). Chaque événement part sur les canaux
 * définis par ServiceBookingNotification (email + interne aujourd'hui,
 * SMS/WhatsApp à venir).
 *
 * Destinataire : le client titulaire de la commande (Notifiable). Un achat
 * invité sans compte n'a pas encore de canal (guest_email à ajouter) — il
 * est ignoré avec un log, comme avant.
 */
class ServiceBookingNotifier
{
    /** Confirmation immédiate après paiement, pour chaque ligne SERVICE. */
    public function confirmForOrder(Order $order): void
    {
        foreach ($this->serviceItems($order) as $item) {
            $this->send($item, ServiceBookingNotification::CONFIRMED);
        }
    }

    /** Annulation de tous les rendez-vous d'une commande (remboursement §80). */
    public function cancelForOrder(Order $order): void
    {
        foreach ($this->serviceItems($order) as $item) {
            $this->send($item, ServiceBookingNotification::CANCELLED);
        }
    }

    public function remind(OrderItem $item): void
    {
        $this->send($item, ServiceBookingNotification::REMINDER);
    }

    public function rescheduled(OrderItem $item, Carbon $previousDatetime): void
    {
        $this->send($item, ServiceBookingNotification::RESCHEDULED, $previousDatetime);
    }

    public function cancelled(OrderItem $item): void
    {
        $this->send($item, ServiceBookingNotification::CANCELLED);
    }

    private function send(OrderItem $item, string $type, ?Carbon $previous = null): void
    {
        $item->loadMissing(['order.user', 'product']);
        $notification = new ServiceBookingNotification($item, $type, $previous);

        $user = $item->order?->user;
        if ($user) {
            $user->notify($notification);

            return;
        }

        Log::warning('ServiceBookingNotifier: commande sans compte client, notification de rendez-vous ignorée.', [
            'order_item_id' => $item->id,
            'type' => $type,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, OrderItem>
     */
    private function serviceItems(Order $order): \Illuminate\Support\Collection
    {
        return $order->items()
            ->where('product_type', 'service')
            ->whereNotNull('reservation_datetime')
            ->with('product')
            ->get();
    }
}
