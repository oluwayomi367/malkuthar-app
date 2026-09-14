<?php

namespace Tests\Feature\Notifications;

use App\Models\Wallet;
use App\Notifications\CommissionAvailableNotification;
use App\Notifications\NewCommissionNotification;
use App\Notifications\WithdrawalFailedNotification;
use App\Notifications\WithdrawalPaidNotification;
use App\Notifications\WithdrawalRequestedNotification;
use App\Services\AffiliationService;
use App\Services\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Affiliate\InteractsWithAffiliation;
use Tests\TestCase;

/**
 * §59 — notifications affilié sur les événements financiers.
 */
class AffiliateNotificationsTest extends TestCase
{
    use InteractsWithAffiliation;
    use RefreshDatabase;

    public function test_new_commission_notifies_each_credited_affiliate(): void
    {
        Notification::fake();

        $jean = $this->affiliate();
        $pierre = $this->affiliate($jean);

        $order = $this->paidOrder($pierre, [$this->digitalAffiliableLine()]);
        app(AffiliationService::class)->distributeCommissions($order);

        Notification::assertSentTo($pierre, NewCommissionNotification::class, fn ($n) => (float) $n->entry->amount === 1500.0);
        Notification::assertSentTo($jean, NewCommissionNotification::class, fn ($n) => (float) $n->entry->amount === 1000.0);
    }

    public function test_duplicate_webhook_does_not_send_duplicate_notifications(): void
    {
        Notification::fake();

        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine()]);

        app(AffiliationService::class)->distributeCommissions($order);
        app(AffiliationService::class)->distributeCommissions($order->fresh()); // §27

        Notification::assertSentToTimes($jean, NewCommissionNotification::class, 1);
    }

    public function test_commission_release_sends_availability_notification(): void
    {
        Notification::fake();
        config(['malkuthar.affiliation.validation_delay_days' => 0]);

        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine()]);
        app(AffiliationService::class)->distributeCommissions($order);

        app(AffiliationService::class)->releaseDueCommissions();

        Notification::assertSentTo($jean, CommissionAvailableNotification::class);
    }

    public function test_withdrawal_lifecycle_sends_requested_paid_and_failed_notifications(): void
    {
        Notification::fake();

        $jean = $this->affiliate();
        Wallet::create(['user_id' => $jean->id, 'currency' => 'XOF', 'balance_available' => 50000]);
        $service = app(WithdrawalService::class);

        $withdrawal = $service->requestWithdrawal($jean, [
            'amount' => 10000, 'method' => 'mobile_money', 'account_details' => ['mobile_money_number' => '+22990000000'],
        ]);
        Notification::assertSentTo($jean, WithdrawalRequestedNotification::class);

        $service->markWithdrawalAsPaid($withdrawal, 'PROV-1');
        Notification::assertSentTo($jean, WithdrawalPaidNotification::class);

        $withdrawal2 = $service->requestWithdrawal($jean, [
            'amount' => 5000, 'method' => 'mobile_money', 'account_details' => ['mobile_money_number' => '+22990000000'],
        ]);
        $service->rollbackFailedWithdrawal($withdrawal2, 'opérateur indisponible');
        Notification::assertSentTo($jean, WithdrawalFailedNotification::class);
    }

    public function test_notification_is_stored_for_the_internal_notification_center(): void
    {
        $jean = $this->affiliate();
        Wallet::create(['user_id' => $jean->id, 'currency' => 'XOF', 'balance_available' => 50000]);

        app(WithdrawalService::class)->requestWithdrawal($jean, [
            'amount' => 10000, 'method' => 'mobile_money', 'account_details' => ['mobile_money_number' => '+22990000000'],
        ]);

        // §59 - "notification interne" : disponible dans $user->notifications.
        $this->assertSame(1, $jean->notifications()->count());
        $this->assertSame('withdrawal.requested', $jean->notifications()->first()->data['type']);
    }
}
