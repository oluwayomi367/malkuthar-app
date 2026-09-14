<?php

namespace Tests\Feature\Affiliate;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Helpers partagés pour les scénarios d'affiliation (commande payée,
 * lignée d'affiliés, ligne digitale éligible).
 */
trait InteractsWithAffiliation
{
    protected function affiliate(?User $parent = null, array $attributes = []): User
    {
        return User::factory()->affiliate()->create(array_merge([
            'parent_id' => $parent?->id,
        ], $attributes));
    }

    /**
     * @param  array<int, array{type: string, affiliable: bool, amount: float}>  $lines
     */
    protected function paidOrder(?User $referring, array $lines): Order
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
            $product = Product::factory()->create([
                'type' => $line['type'],
                'is_affiliable' => $line['affiliable'],
            ]);
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
    protected function digitalAffiliableLine(float $amount = 10000): array
    {
        return ['type' => 'digital', 'affiliable' => true, 'amount' => $amount];
    }
}
