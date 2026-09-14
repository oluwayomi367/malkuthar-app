<?php

namespace Tests\Feature\Affiliate;

use App\Models\LedgerEntry;
use App\Models\Setting;
use App\Models\User;
use App\Services\AffiliationService;
use App\Services\OrderRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §40-§42 — cycle de vie d'une commission : PENDING → AVAILABLE après
 * validation, statuts financiers normalisés, chaque transition consignée,
 * et période de validation réglable depuis l'administration.
 */
class CommissionLifecycleTest extends TestCase
{
    use InteractsWithAffiliation;
    use RefreshDatabase;

    public function test_a_commission_starts_pending_and_its_initial_status_is_recorded(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 14]);
        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine(10000)]);
        app(AffiliationService::class)->distributeCommissions($order->fresh());

        $entry = LedgerEntry::where('order_id', $order->id)->where('level', 1)->firstOrFail();

        $this->assertSame('pending', $entry->status);
        $this->assertTrue($entry->available_at->isFuture());

        // §41 — le statut initial est journalisé.
        $this->assertDatabaseHas('ledger_entry_status_changes', [
            'ledger_entry_id' => $entry->id, 'from_status' => null, 'to_status' => 'pending', 'context' => 'created',
        ]);
    }

    public function test_release_records_the_pending_to_available_transition(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 0]);
        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine(10000)]);
        app(AffiliationService::class)->distributeCommissions($order->fresh());
        app(AffiliationService::class)->releaseDueCommissions();

        $entry = LedgerEntry::where('order_id', $order->id)->where('level', 1)->firstOrFail();
        $this->assertSame('available', $entry->status);

        $this->assertDatabaseHas('ledger_entry_status_changes', [
            'ledger_entry_id' => $entry->id, 'from_status' => 'pending', 'to_status' => 'available',
            'context' => 'commission_release',
        ]);
        $this->assertSame(['pending', 'available'], $entry->statusChanges()->pluck('to_status')->all());
    }

    public function test_refund_records_cancellation_or_reversal_transitions(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 30]);
        $jean = $this->affiliate();

        // Commission encore en attente -> annulation au remboursement.
        $orderA = $this->paidOrder($jean, [$this->digitalAffiliableLine(10000)]);
        $orderA->update(['payment_status' => 'paid', 'status' => 'processing']);
        app(AffiliationService::class)->distributeCommissions($orderA->fresh());
        app(OrderRefundService::class)->refund($orderA->fresh());

        $entryA = LedgerEntry::where('order_id', $orderA->id)->where('source_type', 'commission')->firstOrFail();
        $this->assertSame('cancelled', $entryA->status);
        $this->assertDatabaseHas('ledger_entry_status_changes', [
            'ledger_entry_id' => $entryA->id, 'from_status' => 'pending', 'to_status' => 'cancelled',
            'context' => 'refund_cancellation',
        ]);

        // Commission déjà disponible -> reprise (reversed).
        config(['malkuthar.affiliation.validation_delay_days' => 0]);
        $orderB = $this->paidOrder($jean, [$this->digitalAffiliableLine(20000)]);
        $orderB->update(['payment_status' => 'paid', 'status' => 'processing']);
        app(AffiliationService::class)->distributeCommissions($orderB->fresh());
        app(AffiliationService::class)->releaseDueCommissions();
        app(OrderRefundService::class)->refund($orderB->fresh());

        $entryB = LedgerEntry::where('order_id', $orderB->id)->where('source_type', 'commission')->firstOrFail();
        $this->assertSame('reversed', $entryB->status);
        $this->assertSame(['pending', 'available', 'reversed'], $entryB->statusChanges()->pluck('to_status')->all());
    }

    public function test_admin_configures_the_validation_period(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from('/admin/commission-campaigns')
            ->put('/admin/commission-campaigns/validation-delay', ['validation_delay_days' => 3])
            ->assertRedirect('/admin/commission-campaigns')
            ->assertSessionHas('success');

        $this->assertSame('3', Setting::get('affiliation.validation_delay_days'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'affiliation.validation_delay_updated']);

        // Une nouvelle commission devient disponible 3 jours après la vente.
        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine(10000)]);
        app(AffiliationService::class)->distributeCommissions($order->fresh());

        $entry = LedgerEntry::where('order_id', $order->id)->where('level', 1)->firstOrFail();
        $this->assertEqualsWithDelta(
            now()->addDays(3)->timestamp,
            $entry->available_at->timestamp,
            60,
        );
    }

    public function test_setting_overrides_the_config_value(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 14]);
        Setting::put('affiliation.validation_delay_days', 0);

        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine(10000)]);
        app(AffiliationService::class)->distributeCommissions($order->fresh());
        app(AffiliationService::class)->releaseDueCommissions();

        $this->assertSame('available', LedgerEntry::where('order_id', $order->id)->where('level', 1)->value('status'));
    }
}
