<?php

namespace Tests\Feature\Affiliate;

use App\Models\User;
use App\Services\AffiliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §45-§47 — pages « Mes ventes », « Ma lignée », « Mes gains ».
 */
class AffiliateReportsTest extends TestCase
{
    use InteractsWithAffiliation;
    use RefreshDatabase;

    public function test_non_affiliate_cannot_open_the_reports(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)->get('/affiliate/sales')->assertForbidden();
        $this->actingAs($client)->get('/affiliate/earnings')->assertForbidden();
    }

    public function test_sales_page_lists_commissions_with_an_anonymised_client(): void
    {
        $jean = $this->affiliate();
        $buyer = User::factory()->create(['email' => 'real-buyer@example.com']);

        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine()]);
        $order->update(['user_id' => $buyer->id]);
        app(AffiliationService::class)->distributeCommissions($order->fresh());

        $this->actingAs($jean)
            ->get('/affiliate/sales')
            ->assertOk()
            ->assertSee('Mes ventes')
            ->assertSee('1 500') // commission niveau 1
            ->assertDontSee('real-buyer@example.com'); // §46 - pas de données perso
    }

    public function test_sales_page_shows_the_sale_amount_and_reference(): void
    {
        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine(20000)]);
        app(AffiliationService::class)->distributeCommissions($order->fresh());

        $this->actingAs($jean)
            ->get('/affiliate/sales')
            ->assertOk()
            ->assertSee('Montant')      // colonne §45
            ->assertSee('20 000')       // montant de la vente (base de commission)
            ->assertSee('3 000')        // commission niveau 1 (15 %)
            ->assertSee('COM-');        // référence unique
    }

    public function test_lineage_page_shows_three_levels_with_sales_count(): void
    {
        $jean = $this->affiliate();
        $filleul = $this->affiliate($jean);

        // Une vente référée par le filleul de niveau 1.
        $order = $this->paidOrder($filleul, [$this->digitalAffiliableLine()]);
        app(AffiliationService::class)->distributeCommissions($order->fresh());

        $this->actingAs($jean)
            ->get('/affiliate/lineage')
            ->assertOk()
            ->assertSee('Niveau 1')
            ->assertSee('Niveau 2')
            ->assertSee('Niveau 3')
            ->assertSee('Ventes réalisées');
    }

    public function test_earnings_page_filters_by_product(): void
    {
        $jean = $this->affiliate();

        $orderA = $this->paidOrder($jean, [$this->digitalAffiliableLine(10000)]); // commission niv.1 = 1 500
        $orderB = $this->paidOrder($jean, [$this->digitalAffiliableLine(50000)]); // commission niv.1 = 7 500
        app(AffiliationService::class)->distributeCommissions($orderA->fresh());
        app(AffiliationService::class)->distributeCommissions($orderB->fresh());

        $productA = $orderA->items->first()->product_id;
        $productB = $orderB->items->first()->product_id;

        $refA = \App\Models\LedgerEntry::where('order_id', $orderA->id)->where('level', 1)->value('reference');
        $refB = \App\Models\LedgerEntry::where('order_id', $orderB->id)->where('level', 1)->value('reference');

        $this->actingAs($jean)->get('/affiliate/earnings?product='.$productA)
            ->assertOk()->assertSee($refA)->assertDontSee($refB);

        $this->actingAs($jean)->get('/affiliate/earnings?product='.$productB)
            ->assertOk()->assertSee($refB)->assertDontSee($refA);
    }

    public function test_earnings_balance_is_reconstructed_from_the_ledger(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 0]);
        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine(10000)]);
        app(AffiliationService::class)->distributeCommissions($order->fresh());

        // On casse volontairement le cache du wallet : la page doit
        // afficher le solde recalculé depuis le ledger, pas la colonne.
        $jean->wallet()->update(['balance_available' => 999999]);

        $this->actingAs($jean)
            ->get('/affiliate/earnings')
            ->assertOk()
            ->assertSee('Reconstitué depuis le registre')
            ->assertDontSee('999 999');
    }

    public function test_earnings_page_filters_by_status(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 0]);
        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine()]);
        app(AffiliationService::class)->distributeCommissions($order->fresh());

        // Toutes les écritures : la commission pending apparaît.
        $this->actingAs($jean)->get('/affiliate/earnings')->assertOk()->assertSee('COM-');

        // Filtre statut=available : la commission pending est masquée.
        $this->actingAs($jean)
            ->get('/affiliate/earnings?status=available')
            ->assertOk()
            ->assertSee('Aucune opération sur cette période.');
    }
}
