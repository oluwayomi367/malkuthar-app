<?php

namespace Tests\Feature\Commerce;

use App\Exceptions\ServiceBookingException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ServiceBookingNotification;
use App\Payments\PaymentVerification;
use App\Services\CheckoutService;
use App\Services\OrderRefundService;
use App\Services\Payments\PaymentConfirmationService;
use App\Services\ServiceAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * §16-§19 — services, calendrier, réservation, rappels.
 */
class ServiceBookingTest extends TestCase
{
    use RefreshDatabase;

    private function availability(): ServiceAvailabilityService
    {
        return app(ServiceAvailabilityService::class);
    }

    private function checkout(): CheckoutService
    {
        return app(CheckoutService::class);
    }

    private function serviceProduct(int $duration = 60): Product
    {
        return Product::factory()->service()->create(['duration_minutes' => $duration, 'price' => 15000]);
    }

    private function nextMonday(): Carbon
    {
        return Carbon::now()->next(Carbon::MONDAY)->startOfDay();
    }

    public function test_slots_are_generated_from_the_weekly_schedule(): void
    {
        $service = $this->serviceProduct(60);
        $this->availability()->setWeeklySchedule($service, [
            ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '12:00', 'capacity' => 1],
        ]);

        $slots = $this->availability()->availableSlotsForDate($service, $this->nextMonday());

        $this->assertSame(['09:00', '10:00', '11:00'], $slots);
    }

    public function test_full_day_time_off_removes_all_slots(): void
    {
        $service = $this->serviceProduct(60);
        $monday = $this->nextMonday();
        $this->availability()->setWeeklySchedule($service, [
            ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '12:00'],
        ]);
        $this->availability()->addTimeOff($service, $monday->toDateString(), null, null, 'Jour férié');

        $this->assertSame([], $this->availability()->availableSlotsForDate($service, $monday));
    }

    public function test_break_removes_overlapping_slots_only(): void
    {
        $service = $this->serviceProduct(60);
        $monday = $this->nextMonday();
        $this->availability()->setWeeklySchedule($service, [
            ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '12:00'],
        ]);
        $this->availability()->addTimeOff($service, $monday->toDateString(), '10:00', '11:00', 'Pause déjeuner');

        $this->assertSame(['09:00', '11:00'], $this->availability()->availableSlotsForDate($service, $monday));
    }

    private function bookSlot(Product $service, Carbon $date, string $time, ?User $user = null): Order
    {
        $user ??= User::factory()->create();

        return $this->checkout()->createOrderFromCheckout(
            data: [
                'payment_method' => 'mobile_money',
                'payment_provider' => 'fedapay',
                'items' => [[
                    'product_id' => $service->id,
                    'quantity' => 1,
                    'reservation_date' => $date->toDateString(),
                    'reservation_time' => $time,
                ]],
            ],
            user: $user,
            productsById: collect([$service->id => $service]),
        );
    }

    public function test_checkout_reserves_a_slot_and_reduces_remaining_capacity(): void
    {
        $service = $this->serviceProduct(60);
        $monday = $this->nextMonday();
        $this->availability()->setWeeklySchedule($service, [
            ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 1],
        ]);

        $this->bookSlot($service, $monday, '09:00');

        $this->assertSame([], $this->availability()->availableSlotsForDate($service, $monday));
    }

    public function test_checkout_rejects_a_full_slot(): void
    {
        $service = $this->serviceProduct(60);
        $monday = $this->nextMonday();
        $this->availability()->setWeeklySchedule($service, [
            ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 1],
        ]);

        $this->bookSlot($service, $monday, '09:00');

        $this->expectException(ServiceBookingException::class);
        $this->bookSlot($service, $monday, '09:00');
    }

    public function test_refund_releases_the_slot_and_notifies_cancellation(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $service = $this->serviceProduct(60);
        $monday = $this->nextMonday();
        $this->availability()->setWeeklySchedule($service, [
            ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 1],
        ]);

        $order = $this->bookSlot($service, $monday, '09:00', $user);
        $this->assertSame([], $this->availability()->availableSlotsForDate($service, $monday));

        app(PaymentConfirmationService::class)->confirmPaid(
            $order,
            new PaymentVerification(status: PaymentVerification::PAID, reference: 'ref-svc-1')
        );
        app(OrderRefundService::class)->refund($order->fresh());

        $this->assertSame(['09:00'], $this->availability()->availableSlotsForDate($service, $monday));
        Notification::assertSentTo($user, ServiceBookingNotification::class,
            fn ($n) => $n->type === ServiceBookingNotification::CANCELLED);
    }

    public function test_booking_confirmation_is_sent_on_payment(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $service = $this->serviceProduct(60);
        $monday = $this->nextMonday();
        $this->availability()->setWeeklySchedule($service, [
            ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00'],
        ]);

        $order = $this->bookSlot($service, $monday, '09:00', $user);

        app(PaymentConfirmationService::class)->confirmPaid(
            $order,
            new PaymentVerification(status: PaymentVerification::PAID, reference: 'ref-svc-2')
        );

        Notification::assertSentTo(
            $user,
            ServiceBookingNotification::class,
            fn ($n) => $n->type === ServiceBookingNotification::CONFIRMED
                && in_array('database', $n->via($user), true)
                && in_array('mail', $n->via($user), true)
        );
    }

    public function test_reminder_command_sends_once_within_the_window(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $service = $this->serviceProduct(60);
        $tomorrow = Carbon::now()->addDay();
        $weekday = $tomorrow->dayOfWeek;
        $this->availability()->setWeeklySchedule($service, [
            ['weekday' => $weekday, 'start_time' => '00:00', 'end_time' => '23:59'],
        ]);

        $order = $this->bookSlot($service, $tomorrow->copy()->startOfDay(), $tomorrow->format('H:i'), $user);
        app(PaymentConfirmationService::class)->confirmPaid(
            $order,
            new PaymentVerification(status: PaymentVerification::PAID, reference: 'ref-svc-3')
        );

        $this->artisan('bookings:send-reminders', ['--hours-before' => 48])->assertSuccessful();
        Notification::assertSentTo($user, ServiceBookingNotification::class,
            fn ($n) => $n->type === ServiceBookingNotification::REMINDER);

        $this->artisan('bookings:send-reminders', ['--hours-before' => 48])->assertSuccessful();
        Notification::assertSentToTimes($user, ServiceBookingNotification::class, 2); // 1 confirmation + 1 rappel, pas 2 rappels
    }

    public function test_admin_can_update_schedule_but_regular_user_cannot(): void
    {
        $service = $this->serviceProduct(60);
        $client = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $payload = ['windows' => [
            ['weekday' => 2, 'start_time' => '09:00', 'end_time' => '11:00', 'capacity' => 2],
        ]];

        $this->actingAs($client)
            ->putJson("/admin/services/{$service->id}/schedule", $payload)
            ->assertForbidden();

        $this->actingAs($admin)
            ->putJson("/admin/services/{$service->id}/schedule", $payload)
            ->assertOk();

        $this->assertDatabaseHas('service_schedules', [
            'product_id' => $service->id,
            'weekday' => 2,
            'capacity' => 2,
        ]);
    }

    public function test_admin_reschedules_a_booking_and_the_client_is_notified(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $service = $this->serviceProduct(60);
        $monday = $this->nextMonday();
        $this->availability()->setWeeklySchedule($service, [
            ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '12:00', 'capacity' => 1],
        ]);

        $order = $this->bookSlot($service, $monday, '09:00', $user);
        app(PaymentConfirmationService::class)->confirmPaid(
            $order, new PaymentVerification(status: PaymentVerification::PAID, reference: 'ref-resched')
        );
        $item = $order->items()->first();

        $this->actingAs($admin)
            ->from('/admin/calendar')
            ->post("/admin/calendar/{$item->id}/reschedule", [
                'reservation_date' => $monday->toDateString(),
                'reservation_time' => '11:00',
            ])
            ->assertRedirect('/admin/calendar')
            ->assertSessionHas('success');

        $this->assertSame('11:00', $item->fresh()->reservation_datetime->format('H:i'));
        // L'ancien créneau est de nouveau libre, le nouveau est pris.
        $this->assertContains('09:00', $this->availability()->availableSlotsForDate($service, $monday));
        $this->assertNotContains('11:00', $this->availability()->availableSlotsForDate($service, $monday));

        Notification::assertSentTo($user, ServiceBookingNotification::class,
            fn ($n) => $n->type === ServiceBookingNotification::RESCHEDULED
                && $n->previousDatetime?->format('H:i') === '09:00');
    }

    public function test_admin_cancels_a_booking_and_the_client_is_notified(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $service = $this->serviceProduct(60);
        $monday = $this->nextMonday();
        $this->availability()->setWeeklySchedule($service, [
            ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '10:00', 'capacity' => 1],
        ]);

        $order = $this->bookSlot($service, $monday, '09:00', $user);
        app(PaymentConfirmationService::class)->confirmPaid(
            $order, new PaymentVerification(status: PaymentVerification::PAID, reference: 'ref-cancel')
        );
        $item = $order->items()->first();

        $this->actingAs($admin)
            ->from('/admin/calendar')
            ->post("/admin/calendar/{$item->id}/cancel")
            ->assertRedirect('/admin/calendar')
            ->assertSessionHas('success');

        $this->assertNull($item->fresh()->reservation_datetime);
        $this->assertSame(['09:00'], $this->availability()->availableSlotsForDate($service, $monday));

        Notification::assertSentTo($user, ServiceBookingNotification::class,
            fn ($n) => $n->type === ServiceBookingNotification::CANCELLED);
    }

    public function test_regular_user_cannot_reach_the_bookings_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/calendar')
            ->assertForbidden();
    }
}
