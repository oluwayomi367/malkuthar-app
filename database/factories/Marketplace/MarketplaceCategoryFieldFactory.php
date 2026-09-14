<?php

namespace Database\Factories\Marketplace;

use App\Models\Marketplace\MarketplaceCategoryField;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MarketplaceCategoryField>
 */
class MarketplaceCategoryFieldFactory extends Factory
{
    protected $model = MarketplaceCategoryField::class;

    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'key' => Str::slug($label, '_'),
            'label' => Str::title($label),
            'help_text' => null,
            'type' => 'text',
            'options' => null,
            'validation' => null,
            'is_required' => false,
            'is_filterable' => false,
            'is_variant_axis' => false,
            'position' => 0,
        ];
    }

    public function key(string $key): static
    {
        return $this->state(fn () => ['key' => $key, 'label' => Str::title(str_replace('_', ' ', $key))]);
    }

    public function select(array $options, bool $filterable = true): static
    {
        return $this->state(fn () => [
            'type' => 'select',
            'options' => $options,
            'is_filterable' => $filterable,
        ]);
    }

    public function required(): static
    {
        return $this->state(fn () => ['is_required' => true]);
    }
}
