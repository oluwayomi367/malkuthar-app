<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => fake()->sentence(),
            'type' => 'digital',
            'is_affiliable' => false,
            'price' => fake()->randomElement([5000, 10000, 15000, 20000, 30000]),
            'currency' => 'XOF',
            'status' => 'published',
            'is_available' => true,
        ];
    }

    /** §8 — produit digital éligible au programme d'affiliation. */
    public function digitalAffiliable(): static
    {
        return $this->state(fn () => [
            'type' => 'digital',
            'is_affiliable' => true,
            'file_path' => 'products/'.fake()->uuid().'.pdf',
        ]);
    }

    /** §8 — produit digital explicitement exclu du programme. */
    public function digitalNonAffiliable(): static
    {
        return $this->state(fn () => [
            'type' => 'digital',
            'is_affiliable' => false,
            'file_path' => 'products/'.fake()->uuid().'.pdf',
        ]);
    }

    /** §4 — produit physique : jamais de commission. */
    public function physical(): static
    {
        return $this->state(fn () => [
            'type' => 'physical',
            'is_affiliable' => false,
            'sku' => strtoupper(Str::random(10)),
            'stock_available' => 100,
            'weight' => 0.5,
        ]);
    }

    /** §4 — service : jamais de commission. */
    public function service(): static
    {
        return $this->state(fn () => [
            'type' => 'service',
            'is_affiliable' => false,
            'duration_minutes' => 60,
            'delivery_mode' => 'visioconférence',
        ]);
    }
}
