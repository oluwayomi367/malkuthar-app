<?php

namespace Database\Factories\Marketplace;

use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\VendorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MarketplaceOffer>
 */
class MarketplaceOfferFactory extends Factory
{
    protected $model = MarketplaceOffer::class;

    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'vendor_profile_id' => VendorProfile::factory()->active(),
            'category_id' => MarketplaceCategory::factory(),
            'type' => 'physical',
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'summary' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'price' => fake()->randomElement([5000, 12000, 25000, 40000]),
            'currency' => 'XOF',
            'status' => 'draft',
            'is_online' => false,
            'version' => 0,
        ];
    }

    public function forVendor(VendorProfile $vendor): static
    {
        return $this->state(fn () => ['vendor_profile_id' => $vendor->id]);
    }

    public function inCategory(MarketplaceCategory $category): static
    {
        return $this->state(fn () => ['category_id' => $category->id, 'type' => $category->applicable_offer_types[0] ?? 'physical']);
    }

    public function type(string $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    public function pendingReview(): static
    {
        return $this->state(fn () => ['status' => 'pending_review', 'version' => 1, 'submitted_at' => now()]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'is_online' => true,
            'version' => 1,
            'approved_at' => now(),
            'first_published_at' => now(),
        ]);
    }

    /** Offre physique avec une ligne de détail stock. */
    public function withStock(int $stock = 10): static
    {
        return $this->state(fn () => ['type' => 'physical'])
            ->afterCreating(fn (MarketplaceOffer $offer) => $offer->physical()->create([
                'offer_id' => $offer->id,
                'stock' => $stock,
                'stock_reserved' => 0,
            ]));
    }
}
