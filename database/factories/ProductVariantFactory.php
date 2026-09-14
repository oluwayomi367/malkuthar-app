<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        $size = fake()->randomElement(['S', 'M', 'L', 'XL']);
        $color = fake()->randomElement(['Noir', 'Sable', 'Terre', 'Ivoire']);

        return [
            'product_id' => Product::factory()->physical(),
            'name' => "{$color} / {$size}",
            'options' => ['couleur' => $color, 'taille' => $size],
            'sku' => strtoupper(Str::random(8)),
            'price' => null,
            'stock_available' => 20,
            'stock_reserved' => 0,
            'stock_sold' => 0,
            'is_active' => true,
            'position' => 0,
        ];
    }
}
