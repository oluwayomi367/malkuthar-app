<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\VendorLedgerEntry;
use App\Models\Marketplace\VendorPayout;
use App\Models\Marketplace\VendorProfile;
use App\Models\Setting;
use App\Services\Marketplace\MarketplaceCheckoutService;
use App\Services\Marketplace\MarketplacePaymentConfirmationService;
use App\Services\Marketplace\VendorPayoutService;
use App\Services\Marketplace\VendorSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SettlementTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithMarketplace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableMarketplace();
        Setting::put('marketplace.default_commission_rate', 0.15);
        Setting::put('marketplace.settlement_hold_days', 10);
        Setting::put('marketplace.min_payout_amount', 5000);
        Setting::put('marketplace.payout_account_cooldown_hours', 24);
        Notification::fake();
    }

    /** Crée une commande payée d'un montant net donné pour un vendeur. */
    private function paidSuborderFor(VendorProfile $vendor, float $price): \App\Models\Marketplace\MarketplaceSuborder
    {
        $offer = MarketplaceOffer::factory()->approved()->for($vendor, 'vendor')->create(['price' => $price]);
        $order = app(MarketplaceCheckoutService::class)->place([$offer->id => 1], ['email' => 'b@example.com']);
        app(MarketplacePaymentConfirmationService::class)->confirmPaid($order->fresh());

        return $order->fresh()->suborders()->first();
    }

    public function test_confirmed_sale_credits_a_pending_ledger_entry(): void
    {
        $vendor = VendorProfile::factory()->active()->create();
        $sub = $this->paidSuborderFor($vendor, 20000);

        $entry = VendorLedgerEntry::where('reference', 'sale:'.$sub->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame('pending', $entry->status);
        $this->assertEqualsWithDelta(17000, (float) $entry->amount, 0.01); // 20000 - 15%

        $balance = app(VendorSettlementService::class)->recompute($vendor->fresh());
        $this->assertEqualsWithDelta(17000, (float) $balance->pending_amount, 0.01);
        $this->assertEqualsWithDelta(0, (float) $balance->available_amount, 0.01);
    }

    public function test_release_due_moves_matured_credits_to_available_and_skips_suspended(): void
    {
        $vendor = VendorProfile::factory()->active()->create();
        $sub = $this->paidSuborderFor($vendor, 10000);

        // Pas encore échu.
        $this->assertSame(0, app(VendorSettlementService::class)->releaseDue());

        // On force l'échéance.
        VendorLedgerEntry::where('reference', 'sale:'.$sub->id)->update(['available_at' => now()->subDay()]);

        $this->assertSame(1, app(VendorSettlementService::class)->releaseDue());
        $balance = app(VendorSettlementService::class)->recompute($vendor->fresh());
        $this->assertEqualsWithDelta(8500, (float) $balance->available_amount, 0.01);
        $this->assertEqualsWithDelta(0, (float) $balance->pending_amount, 0.01);

        // Vendeur suspendu : pas de libération.
        $suspended = VendorProfile::factory()->active()->create();
        $sub2 = $this->paidSuborderFor($suspended, 10000);
        VendorLedgerEntry::where('reference', 'sale:'.$sub2->id)->update(['available_at' => now()->subDay()]);
        $suspended->update(['status' => 'suspended']);

        $this->assertSame(0, app(VendorSettlementService::class)->releaseDue());
    }

    public function test_release_command_runs(): void
    {
        $this->artisan('marketplace:release-earnings')->assertSuccessful();
    }

    public function test_reversing_a_released_sale_claws_back_available_funds(): void
    {
        $vendor = VendorProfile::factory()->active()->create();
        $sub = $this->paidSuborderFor($vendor, 10000);
        VendorLedgerEntry::where('reference', 'sale:'.$sub->id)->update(['available_at' => now()->subDay()]);
        app(VendorSettlementService::class)->releaseDue();

        app(VendorSettlementService::class)->reverseForSuborder($sub->fresh(), 'Client remboursé');

        $sale = VendorLedgerEntry::where('reference', 'sale:'.$sub->id)->first();
        $this->assertSame('reversed', $sale->status);
        $this->assertDatabaseHas('vendor_ledger_entries', [
            'reference' => 'refund_reversal:'.$sub->id, 'direction' => 'debit',
        ]);

        $balance = app(VendorSettlementService::class)->recompute($vendor->fresh());
        $this->assertEqualsWithDelta(0, (float) $balance->available_amount, 0.01);
        $this->assertEqualsWithDelta(8500, (float) $balance->lifetime_reversed, 0.01);
    }

    public function test_manual_adjustment_credit_and_debit(): void
    {
        $vendor = VendorProfile::factory()->active()->create();
        $admin = $this->admin();
        $svc = app(VendorSettlementService::class);

        $svc->adjust($vendor, $admin, 3000, 'Geste commercial');
        $this->assertEqualsWithDelta(3000, (float) $svc->recompute($vendor->fresh())->available_amount, 0.01);

        $svc->adjust($vendor, $admin, -1000, 'Correction');
        $this->assertEqualsWithDelta(2000, (float) $svc->recompute($vendor->fresh())->available_amount, 0.01);
    }

    public function test_payout_request_moves_available_to_reserved(): void
    {
        $vendor = VendorProfile::factory()->active()->create();
        $vendor->payoutAccounts()->create(['method' => 'mobile_money', 'label' => 'MTN', 'details' => ['number' => '070000'], 'is_default' => true]);

        app(VendorSettlementService::class)->adjust($vendor, $this->admin(), 20000, 'seed');

        $payout = app(VendorPayoutService::class)->request($vendor->fresh(), 12000, null, $vendor->user);

        $this->assertSame('requested', $payout->status);
        $balance = app(VendorSettlementService::class)->recompute($vendor->fresh());
        $this->assertEqualsWithDelta(8000, (float) $balance->available_amount, 0.01);
        $this->assertEqualsWithDelta(12000, (float) $balance->reserved_amount, 0.01);
    }

    public function test_payout_request_enforces_minimum_and_available_balance(): void
    {
        $vendor = VendorProfile::factory()->active()->create();
        $vendor->payoutAccounts()->create(['method' => 'mobile_money', 'label' => 'MTN', 'details' => ['number' => 'x'], 'is_default' => true]);
        app(VendorSettlementService::class)->adjust($vendor, $this->admin(), 3000, 'seed');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(VendorPayoutService::class)->request($vendor->fresh(), 3000, null, $vendor->user); // < 5000 min
    }

    public function test_payout_paid_then_failed_lifecycle(): void
    {
        $vendor = VendorProfile::factory()->active()->create();
        $acc = $vendor->payoutAccounts()->create(['method' => 'mobile_money', 'label' => 'MTN', 'details' => ['number' => 'x'], 'is_default' => true]);
        app(VendorSettlementService::class)->adjust($vendor, $this->admin(), 30000, 'seed');
        $svc = app(VendorPayoutService::class);
        $admin = $this->admin();

        $p1 = $svc->request($vendor->fresh(), 10000, $acc->id, $vendor->user);
        $svc->markPaid($p1->fresh(), $admin, 'MM-REF-1');
        $b = app(VendorSettlementService::class)->recompute($vendor->fresh());
        $this->assertEqualsWithDelta(20000, (float) $b->available_amount, 0.01);   // 30000 - 10000
        $this->assertEqualsWithDelta(0, (float) $b->reserved_amount, 0.01);
        $this->assertEqualsWithDelta(10000, (float) $b->lifetime_paid, 0.01);

        $p2 = $svc->request($vendor->fresh(), 8000, $acc->id, $vendor->user);
        $svc->markFailed($p2->fresh(), $admin, 'Numéro invalide');
        $b = app(VendorSettlementService::class)->recompute($vendor->fresh());
        $this->assertEqualsWithDelta(20000, (float) $b->available_amount, 0.01);   // fonds restitués
        $this->assertEqualsWithDelta(0, (float) $b->reserved_amount, 0.01);
    }

    public function test_payout_account_change_cooldown_blocks_a_request(): void
    {
        $vendor = VendorProfile::factory()->active()->create();
        $vendor->payoutAccounts()->create([
            'method' => 'mobile_money', 'label' => 'MTN', 'details' => ['number' => 'x'],
            'is_default' => true, 'changed_at' => now()->subHour(),
        ]);
        app(VendorSettlementService::class)->adjust($vendor, $this->admin(), 20000, 'seed');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(VendorPayoutService::class)->request($vendor->fresh(), 10000, null, $vendor->user);
    }

    public function test_admin_payout_queue_actions_over_http(): void
    {
        $vendor = VendorProfile::factory()->active()->create();
        $acc = $vendor->payoutAccounts()->create(['method' => 'mobile_money', 'label' => 'MTN', 'details' => ['number' => 'x'], 'is_default' => true]);
        app(VendorSettlementService::class)->adjust($vendor, $this->admin(), 20000, 'seed');
        $payout = app(VendorPayoutService::class)->request($vendor->fresh(), 10000, $acc->id, $vendor->user);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.marketplace.payouts.index'))->assertOk()->assertSee($payout->reference);
        $this->actingAs($admin)->post(route('admin.marketplace.payouts.approve', $payout))->assertRedirect();
        $this->assertSame('approved', $payout->fresh()->status);
        $this->actingAs($admin)->post(route('admin.marketplace.payouts.mark-paid', $payout), ['provider_reference' => 'REF'])->assertRedirect();
        $this->assertSame('paid', $payout->fresh()->status);
    }

    public function test_vendor_settlement_and_payout_pages_render(): void
    {
        $vendor = VendorProfile::factory()->active()->create();
        $this->paidSuborderFor($vendor, 10000);

        $this->actingAs($vendor->user)->get(route('vendor.settlement.index'))->assertOk()->assertSee('Mes revenus');
        $this->actingAs($vendor->user)->get(route('vendor.payouts.index'))->assertOk()->assertSee('Solde disponible');

        $this->actingAs($vendor->user)->post(route('vendor.payouts.accounts.store'), [
            'method' => 'mobile_money', 'label' => 'Moov', 'number' => '069999',
        ])->assertRedirect();
        $this->assertDatabaseCount('marketplace_payout_accounts', 1);
    }
}
