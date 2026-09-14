<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Payments\PaymentConfirmationService;
use App\Payments\PaymentVerification;
use App\Services\ProductSalesReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Affiliate\InteractsWithAffiliation;
use Tests\TestCase;

/**
 * Ventes & commissions par article (§4/§5, §39) : la fiche produit admin
 * expose les unités vendues, le CA et les commissions générées ; un
 * produit non affiliable affiche « aucune commission ».
 */
class AdminProductSalesReportTest extends TestCase
{
    use InteractsWithAffiliation;
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_report_counts_paid_sales_pending_cod_and_net_commissions(): void
    {
        $affiliate = $this->affiliate();
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);

        // Une commande payée (COD encaissée) + une commande COD encore en attente.
        $paid = Order::factory()->cod()->withItem($product, 2)->create([
            'referring_affiliate_id' => $affiliate->id,
        ]);
        app(PaymentConfirmationService::class)->confirmPaid(
            $paid,
            new PaymentVerification(status: PaymentVerification::PAID, reference: 'COD-'.$paid->order_number),
        );

        Order::factory()->cod()->withItem($product, 1)->create();

        $report = app(ProductSalesReport::class)->forProduct($product->fresh());

        $this->assertTrue($report['applicable']);
        $this->assertSame(2, $report['units_sold']);
        $this->assertSame(1, $report['orders_count']);
        $this->assertEqualsWithDelta(20000.0, $report['revenue'], 0.01);
        $this->assertSame(1, $report['pending_units']);
        // 30 % de 20 000 réparti sur les 3 niveaux, mais seul le niveau 1 existe ici.
        $this->assertEqualsWithDelta(3000.0, $report['commission_by_level'][1], 0.01);
        $this->assertEqualsWithDelta(3000.0, $report['commission_net'], 0.01);
    }

    public function test_show_page_displays_the_sales_panel(): void
    {
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);
        $order = Order::factory()->cod()->paid()->withItem($product)->create();

        $this->actingAs($this->admin())
            ->get("/admin/products/{$product->id}")
            ->assertOk()
            ->assertSee('Ventes &amp; commissions', false)
            ->assertSee('Unités vendues');
    }

    public function test_non_affiliable_product_reports_no_commission(): void
    {
        $product = Product::factory()->physical()->create(['stock_available' => 10]);
        Order::factory()->cod()->paid()->withItem($product)->create();

        $report = app(ProductSalesReport::class)->forProduct($product->fresh());
        $this->assertFalse($report['applicable']);

        $this->actingAs($this->admin())
            ->get("/admin/products/{$product->id}")
            ->assertOk()
            ->assertSee("aucune commission d'affiliation n'est calculée", false);
    }

    public function test_products_index_shows_sales_columns(): void
    {
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);
        Order::factory()->cod()->paid()->withItem($product, 3)->create();

        $this->actingAs($this->admin())
            ->get('/admin/products')
            ->assertOk()
            ->assertSee('Ventes')
            ->assertSee('Commissions');
    }
}
