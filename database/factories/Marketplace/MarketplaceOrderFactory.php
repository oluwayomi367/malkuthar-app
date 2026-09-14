<?php

namespace Database\Factories\Marketplace;

use App\Models\Marketplace\MarketplaceOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketplaceOrder>
 */
class MarketplaceOrderFactory extends Factory
{
    protected $model = MarketplaceOrder::class;

    public function definition(): array
    {
        $number = 'MP-'.fake()->unique()->numerify('######');

        return [
            'order_number' => $number,
            'buyer_email' => fake()->safeEmail(),
            'buyer_phone' => fake()->numerify('+229########'),
            'currency' => 'XOF',
            'items_total' => 0,
            'grand_total' => 0,
            'payment_status' => 'pending',
            'payment_provider' => 'cod',
            'payment_reference' => $number,
            'status' => 'pending',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'payment_status' => 'paid',
            'status' => 'processing',
            'paid_at' => now(),
        ]);
    }
}
