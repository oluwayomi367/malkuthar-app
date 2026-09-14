<?php

namespace Database\Factories;

use App\Models\CommissionCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommissionCampaign>
 */
class CommissionCampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Campagne '.fake()->unique()->randomLetter(),
            'rate_l1' => 0.15,
            'rate_l2' => 0.10,
            'rate_l3' => 0.05,
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
            'is_default' => false,
            'priority' => 0,
        ];
    }

    public function rates(float $l1, float $l2, float $l3): static
    {
        return $this->state(fn () => ['rate_l1' => $l1, 'rate_l2' => $l2, 'rate_l3' => $l3]);
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
