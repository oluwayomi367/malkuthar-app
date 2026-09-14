<?php

namespace Database\Factories\Marketplace;

use App\Models\Marketplace\MarketplaceCommissionRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketplaceCommissionRule>
 */
class MarketplaceCommissionRuleFactory extends Factory
{
    protected $model = MarketplaceCommissionRule::class;

    public function definition(): array
    {
        return [
            'scope' => 'platform',
            'category_id' => null,
            'vendor_profile_id' => null,
            'offer_type' => null,
            'rate' => 0.15,
            'min_fee' => null,
            'max_fee' => null,
            'starts_at' => null,
            'ends_at' => null,
            'priority' => 0,
            'is_active' => true,
        ];
    }

    public function platform(float $rate): static
    {
        return $this->state(fn () => ['scope' => 'platform', 'rate' => $rate]);
    }

    public function forOfferType(string $type, float $rate): static
    {
        return $this->state(fn () => ['scope' => 'offer_type', 'offer_type' => $type, 'rate' => $rate]);
    }

    public function forCategory(string $categoryId, float $rate): static
    {
        return $this->state(fn () => ['scope' => 'category', 'category_id' => $categoryId, 'rate' => $rate]);
    }

    public function forVendor(string $vendorProfileId, float $rate): static
    {
        return $this->state(fn () => ['scope' => 'vendor', 'vendor_profile_id' => $vendorProfileId, 'rate' => $rate]);
    }

    public function window(?string $startsAt, ?string $endsAt): static
    {
        return $this->state(fn () => ['starts_at' => $startsAt, 'ends_at' => $endsAt]);
    }
}
