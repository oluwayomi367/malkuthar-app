<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Services\ServiceBookingNotifier;
use Illuminate\Console\Command;

/**
 * §19 — rappel avant rendez-vous, envoyé une seule fois par réservation
 * (order_items.reminder_sent_at). À planifier (voir bootstrap/app.php).
 */
class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders {--hours-before=24 : Fenêtre avant le rendez-vous}';

    protected $description = 'Envoie un rappel (email + notification interne) pour les rendez-vous à venir dans la fenêtre configurée.';

    public function handle(ServiceBookingNotifier $notifier): int
    {
        $hoursBefore = (int) $this->option('hours-before');

        $items = OrderItem::query()
            ->where('product_type', 'service')
            ->whereNull('reminder_sent_at')
            ->whereNotNull('reservation_datetime')
            ->whereBetween('reservation_datetime', [now(), now()->addHours($hoursBefore)])
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid'))
            ->with(['order.user', 'product'])
            ->get();

        $sent = 0;

        foreach ($items as $item) {
            $notifier->remind($item);
            $item->update(['reminder_sent_at' => now()]);
            $sent++;
        }

        $this->info("{$sent} rappel(s) de rendez-vous envoyé(s).");

        return self::SUCCESS;
    }
}
