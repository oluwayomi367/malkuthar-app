<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_number' => 'MAL-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'mobile_money',
            'payment_provider' => 'fedapay',
            'currency' => 'XOF',
            'subtotal' => 10000,
            'discount_total' => 0,
            'shipping_fee' => 0,
            'total_amount' => 10000,
        ];
    }

    /** Paiement à la livraison, en attente d'encaissement. */
    public function cod(): static
    {
        return $this->state(fn () => [
            'payment_provider' => 'cod',
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
    }

    /** Commande déjà réglée. */
    public function paid(): static
    {
        return $this->state(fn () => [
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);
    }

    /**
     * Ajoute une ligne après création. Le type/éligibilité sont snapshotés
     * comme en production (§4/§5).
     */
    public function withItem(Product $product, int $quantity = 1, ?float $amount = null): static
    {
        return $this->afterCreating(function (Order $order) use ($product, $quantity, $amount) {
            $unit = $amount ?? (float) ($product->promotional_price ?? $product->price);
            $total = round($unit * $quantity, 2);
            $eligible = $product->type === 'digital' && (bool) $product->is_affiliable;

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_type' => $product->type,
                'is_affiliable_snapshot' => $eligible,
                'quantity' => $quantity,
                'unit_price' => $unit,
                'discount_amount' => 0,
                'total_price' => $total,
                'currency' => $order->currency,
                'commission_base_amount' => $eligible ? $total : null,
            ]);

            $order->update([
                'subtotal' => $order->items()->sum('total_price'),
                'total_amount' => $order->items()->sum('total_price') + (float) $order->shipping_fee,
                'has_digital_items' => $order->items()->where('product_type', 'digital')->exists(),
                'has_physical_items' => $order->items()->where('product_type', 'physical')->exists(),
                'has_service_items' => $order->items()->where('product_type', 'service')->exists(),
            ]);
        });
    }
}
