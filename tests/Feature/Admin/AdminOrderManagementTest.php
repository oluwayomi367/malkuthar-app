<?php

namespace Tests\Feature\Admin;

use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Affiliate\InteractsWithAffiliation;
use Tests\TestCase;

/**
 * §60, §22/§26 — l'administrateur suit le cycle de vie des commandes et
 * encaisse les paiements à la livraison depuis /admin/orders.
 */
class AdminOrderManagementTest extends TestCase
{
    use InteractsWithAffiliation;
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_non_admin_cannot_reach_orders(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/orders')->assertForbidden();
    }

    public function test_index_filters_by_provider(): void
    {
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);
        $cod = Order::factory()->cod()->withItem($product)->create();
        $psp = Order::factory()->withItem($product)->create(['payment_provider' => 'fedapay']);

        $this->actingAs($this->admin())
            ->get('/admin/orders?provider=cod')
            ->assertOk()
            ->assertSee($cod->order_number)
            ->assertDontSee($psp->order_number);
    }

    public function test_show_page_renders_with_action_panels(): void
    {
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);
        $order = Order::factory()->cod()->withItem($product)->create();

        $this->actingAs($this->admin())
            ->get("/admin/orders/{$order->id}")
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Encaisser')
            ->assertSee('Annuler');
    }

    public function test_collecting_cod_marks_the_order_paid_and_distributes_commissions(): void
    {
        $affiliate = $this->affiliate();
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);

        $order = Order::factory()->cod()->withItem($product)->create([
            'referring_affiliate_id' => $affiliate->id,
        ]);

        $this->actingAs($this->admin())
            ->post("/admin/orders/{$order->id}/collect-cod")
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertNotNull($order->cod_collected_at);
        $this->assertSame('COD-'.$order->order_number, $order->payment_reference);

        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'user_id' => $affiliate->id,
            'source_type' => 'commission',
            'level' => 1,
        ]);
        $this->assertEqualsWithDelta(
            1500.0,
            (float) LedgerEntry::where('order_id', $order->id)->where('level', 1)->value('amount'),
            0.01,
        );

        $this->assertDatabaseHas('audit_logs', ['action' => 'order.cod_collected']);
    }

    public function test_collecting_cod_marks_physical_stock_sold(): void
    {
        $product = Product::factory()->physical()->create(['stock_available' => 10]);
        $order = Order::factory()->cod()->withItem($product)->create();
        $order->items->each(fn ($i) => app(\App\Services\StockService::class)->reserve($i));

        $this->actingAs($this->admin())->post("/admin/orders/{$order->id}/collect-cod")->assertRedirect();

        $product->refresh();
        $this->assertSame(1, (int) $product->stock_sold);
        $this->assertSame(0, (int) $product->stock_reserved);
    }

    public function test_collect_cod_is_rejected_for_non_cod_or_already_paid_orders(): void
    {
        $product = Product::factory()->digitalAffiliable()->create();

        $psp = Order::factory()->withItem($product)->create(['payment_provider' => 'fedapay']);
        $this->actingAs($this->admin())->post("/admin/orders/{$psp->id}/collect-cod")->assertStatus(422);

        $paidCod = Order::factory()->cod()->paid()->withItem($product)->create();
        $this->actingAs($this->admin())->post("/admin/orders/{$paidCod->id}/collect-cod")->assertStatus(422);
    }

    public function test_cancelling_an_unpaid_cod_order_releases_stock_and_creates_no_commission(): void
    {
        $affiliate = $this->affiliate();
        $product = Product::factory()->physical()->create(['stock_available' => 10]);
        $order = Order::factory()->cod()->withItem($product)->create([
            'referring_affiliate_id' => $affiliate->id,
        ]);
        $order->items->each(fn ($i) => app(\App\Services\StockService::class)->reserve($i));

        $this->actingAs($this->admin())
            ->post("/admin/orders/{$order->id}/cancel", ['reason' => 'Client injoignable'])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('cancelled', $order->status);

        $product->refresh();
        $this->assertSame(10, (int) $product->stock_available);
        $this->assertSame(0, (int) $product->stock_reserved);

        $this->assertSame(0, LedgerEntry::where('order_id', $order->id)->count());
    }

    public function test_refunding_a_paid_order_reverses_commissions(): void
    {
        $affiliate = $this->affiliate();
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);
        $order = Order::factory()->cod()->withItem($product)->create([
            'referring_affiliate_id' => $affiliate->id,
        ]);

        $this->actingAs($this->admin())->post("/admin/orders/{$order->id}/collect-cod")->assertRedirect();
        $this->assertSame(1, LedgerEntry::where('order_id', $order->id)->where('source_type', 'commission')->where('status', 'pending')->count());

        $this->actingAs($this->admin())
            ->post("/admin/orders/{$order->id}/refund", ['reason' => 'Retour produit'])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame('cancelled', LedgerEntry::where('order_id', $order->id)->where('source_type', 'commission')->value('status'));
    }

    public function test_fulfilment_status_can_be_advanced_on_a_paid_physical_order(): void
    {
        $product = Product::factory()->physical()->create(['stock_available' => 10]);
        $order = Order::factory()->cod()->withItem($product)->create();
        $order->items->each(fn ($i) => app(\App\Services\StockService::class)->reserve($i));
        $this->actingAs($this->admin())->post("/admin/orders/{$order->id}/collect-cod")->assertRedirect();

        $this->actingAs($this->admin())
            ->post("/admin/orders/{$order->id}/fulfilment", [
                'shipping_status' => 'delivered',
                'shipping_carrier' => 'DHL',
                'tracking_number' => 'ABC123',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('delivered', $order->shipping_status);
        $this->assertSame('ABC123', $order->tracking_number);
        $this->assertSame('completed', $order->status);
    }
}
