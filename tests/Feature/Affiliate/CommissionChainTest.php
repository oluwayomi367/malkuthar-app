<?php

namespace Tests\Feature\Affiliate;

use App\Exceptions\CommissionDistributionException;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\AffiliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests obligatoires du système d'affiliation — §84 (Tests 1 à 7) et §85
 * (Tests A à E). Le montant éligible de référence est 10 000 (§29).
 */
class CommissionChainTest extends TestCase
{
    use RefreshDatabase;

    private function service(): AffiliationService
    {
        return app(AffiliationService::class);
    }

    private function affiliate(?User $parent = null, array $attributes = []): User
    {
        return User::factory()->affiliate()->create(array_merge([
            'parent_id' => $parent?->id,
        ], $attributes));
    }

    /**
     * Crée une commande payée avec une liste de lignes.
     *
     * @param  array<int, array{type: string, affiliable: bool, amount: float}>  $lines
     */
    private function paidOrder(?User $referring, array $lines): Order
    {
        $order = Order::create([
            'user_id' => User::factory()->create()->id,
            'referring_affiliate_id' => $referring?->id,
            'order_number' => 'TEST-'.Str::upper(Str::random(10)),
            'status' => 'processing',
            'payment_status' => 'paid',
            'currency' => 'XOF',
            'subtotal' => array_sum(array_column($lines, 'amount')),
            'total_amount' => array_sum(array_column($lines, 'amount')),
        ]);

        foreach ($lines as $line) {
            $product = Product::factory()->create(['type' => $line['type'], 'is_affiliable' => $line['affiliable']]);
            $eligible = $line['type'] === 'digital' && $line['affiliable'];

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_type' => $line['type'],
                'is_affiliable_snapshot' => $eligible,
                'quantity' => 1,
                'unit_price' => $line['amount'],
                'discount_amount' => 0,
                'total_price' => $line['amount'],
                'currency' => 'XOF',
                'commission_base_amount' => $eligible ? $line['amount'] : null,
            ]);
        }

        return $order->fresh('items');
    }

    /** @return array{type: string, affiliable: bool, amount: float} */
    private function digitalAffiliableLine(float $amount = 10000): array
    {
        return ['type' => 'digital', 'affiliable' => true, 'amount' => $amount];
    }

    private function commissionFor(User $user, int $level): ?LedgerEntry
    {
        return LedgerEntry::where('user_id', $user->id)
            ->where('source_type', 'commission')
            ->where('level', $level)
            ->first();
    }

    // ----- §84 -----

    public function test_1_direct_sale_credits_level_1_only(): void
    {
        $jean = $this->affiliate();

        $this->service()->distributeCommissions($this->paidOrder($jean, [$this->digitalAffiliableLine()]));

        $this->assertEqualsWithDelta(1500, (float) $this->commissionFor($jean, 1)?->amount, 0.001);
        $this->assertSame(1, LedgerEntry::where('source_type', 'commission')->count());
        $this->assertEqualsWithDelta(1500, (float) $jean->wallet->fresh()->balance_pending, 0.001);
    }

    public function test_2_two_levels(): void
    {
        $jean = $this->affiliate();
        $pierre = $this->affiliate($jean);

        $this->service()->distributeCommissions($this->paidOrder($pierre, [$this->digitalAffiliableLine()]));

        $this->assertEqualsWithDelta(1500, (float) $this->commissionFor($pierre, 1)?->amount, 0.001);
        $this->assertEqualsWithDelta(1000, (float) $this->commissionFor($jean, 2)?->amount, 0.001);
    }

    public function test_3_three_levels(): void
    {
        $jean = $this->affiliate();
        $pierre = $this->affiliate($jean);
        $paul = $this->affiliate($pierre);

        $this->service()->distributeCommissions($this->paidOrder($paul, [$this->digitalAffiliableLine()]));

        $this->assertEqualsWithDelta(1500, (float) $this->commissionFor($paul, 1)?->amount, 0.001);
        $this->assertEqualsWithDelta(1000, (float) $this->commissionFor($pierre, 2)?->amount, 0.001);
        $this->assertEqualsWithDelta(500, (float) $this->commissionFor($jean, 3)?->amount, 0.001);
        $this->assertSame(3, LedgerEntry::where('source_type', 'commission')->count());
    }

    public function test_4_no_commission_beyond_level_3(): void
    {
        $root = $this->affiliate();
        $jean = $this->affiliate($root);
        $pierre = $this->affiliate($jean);
        $paul = $this->affiliate($pierre);
        $brice = $this->affiliate($paul);

        $this->service()->distributeCommissions($this->paidOrder($brice, [$this->digitalAffiliableLine()]));

        $this->assertNull($this->commissionFor($root, 4));
        $this->assertSame(0, LedgerEntry::where('user_id', $root->id)->count());
        $this->assertSame(3, LedgerEntry::where('source_type', 'commission')->count());
    }

    public function test_5_unpaid_order_creates_no_commission(): void
    {
        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine()]);
        $order->update(['payment_status' => 'pending']);

        $this->expectException(CommissionDistributionException::class);
        $this->service()->distributeCommissions($order->fresh());
    }

    public function test_7_duplicate_event_creates_single_commission_set(): void
    {
        $jean = $this->affiliate();
        $pierre = $this->affiliate($jean);
        $order = $this->paidOrder($pierre, [$this->digitalAffiliableLine()]);

        $this->service()->distributeCommissions($order);
        $this->service()->distributeCommissions($order->fresh()); // §27

        $this->assertSame(2, LedgerEntry::where('source_type', 'commission')->count());
        $this->assertEqualsWithDelta(1500, (float) $pierre->wallet->fresh()->balance_pending, 0.001);
    }

    // ----- §85 -----

    public function test_b_digital_non_affiliable_generates_nothing(): void
    {
        $jean = $this->affiliate();

        $this->service()->distributeCommissions($this->paidOrder($jean, [
            ['type' => 'digital', 'affiliable' => false, 'amount' => 10000],
        ]));

        $this->assertSame(0, LedgerEntry::where('source_type', 'commission')->count());
    }

    public function test_c_and_d_physical_and_service_generate_nothing(): void
    {
        $jean = $this->affiliate();

        $this->service()->distributeCommissions($this->paidOrder($jean, [
            ['type' => 'physical', 'affiliable' => false, 'amount' => 20000],
            ['type' => 'service', 'affiliable' => false, 'amount' => 30000],
        ]));

        $this->assertSame(0, LedgerEntry::where('source_type', 'commission')->count());
    }

    public function test_e_mixed_cart_only_pays_on_eligible_digital_line(): void
    {
        $jean = $this->affiliate();
        $pierre = $this->affiliate($jean);

        // §5 — l'exemple du cahier : seul l'ebook à 10 000 est éligible.
        $order = $this->paidOrder($pierre, [
            $this->digitalAffiliableLine(10000),
            ['type' => 'digital', 'affiliable' => false, 'amount' => 10000],
            ['type' => 'physical', 'affiliable' => false, 'amount' => 20000],
            ['type' => 'service', 'affiliable' => false, 'amount' => 30000],
        ]);

        $this->service()->distributeCommissions($order);

        $this->assertEqualsWithDelta(1500, (float) $this->commissionFor($pierre, 1)?->amount, 0.001);
        $this->assertEqualsWithDelta(1000, (float) $this->commissionFor($jean, 2)?->amount, 0.001);
        $this->assertEqualsWithDelta(2500, (float) LedgerEntry::where('source_type', 'commission')->sum('amount'), 0.001);
    }

    public function test_all_commissions_start_pending(): void
    {
        $jean = $this->affiliate();

        $this->service()->distributeCommissions($this->paidOrder($jean, [$this->digitalAffiliableLine()]));

        // §40 — jamais disponible immédiatement.
        $this->assertSame(0, LedgerEntry::where('source_type', 'commission')->where('status', '!=', 'pending')->count());
    }
}
