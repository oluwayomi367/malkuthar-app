<?php

namespace Database\Factories\Marketplace;

use App\Models\Marketplace\MarketplaceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MarketplaceCategory>
 */
class MarketplaceCategoryFactory extends Factory
{
    protected $model = MarketplaceCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'parent_id' => null,
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => fake()->sentence(),
            'position' => 0,
            'is_active' => true,
            'applicable_offer_types' => ['physical', 'digital', 'service', 'training'],
            'commission_rate' => null,
            'ruleset' => ['require_approval' => true],
        ];
    }

    public function childOf(MarketplaceCategory $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->getKey()]);
    }

    public function types(array $types): static
    {
        return $this->state(fn () => ['applicable_offer_types' => $types]);
    }

    public function commission(?float $rate): static
    {
        return $this->state(fn () => ['commission_rate' => $rate]);
    }
}
