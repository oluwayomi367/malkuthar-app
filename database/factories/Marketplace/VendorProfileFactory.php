<?php

namespace Database\Factories\Marketplace;

use App\Models\Marketplace\VendorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VendorProfile>
 */
class VendorProfileFactory extends Factory
{
    protected $model = VendorProfile::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'user_id' => User::factory(),
            'display_name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'legal_name' => $name.' SARL',
            'bio' => fake()->sentence(12),
            'region' => fake()->randomElement(['Littoral', 'Atlantique', 'Ouémé', 'Plateau']),
            'country' => 'BJ',
            'city' => fake()->randomElement(['Cotonou', 'Porto-Novo', 'Abomey-Calavi']),
            'contact_email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('+229 ## ## ## ##'),
            'status' => 'pending',
            'is_trusted' => false,
            'payout_currency' => 'XOF',
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => 'Test',
        ]);
    }

    public function trusted(): static
    {
        return $this->state(fn () => ['is_trusted' => true]);
    }

    public function commission(float $rate): static
    {
        return $this->state(fn () => ['commission_rate_override' => $rate]);
    }
}
