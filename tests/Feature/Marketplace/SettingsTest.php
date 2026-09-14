<?php

namespace Tests\Feature\Marketplace;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\MarketplaceSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Réglages d'exploitation du module Marketplace (activation + paliers de
 * commission affichés sur la page publique « Tarifs vendeur »).
 */
class SettingsTest extends TestCase
{
    use InteractsWithMarketplace;
    use RefreshDatabase;

    public function test_admin_toggles_the_marketplace_flag_and_pricing_tiers(): void
    {
        $this->assertFalse(MarketplaceSettings::enabled());

        $this->actingAs($this->admin())->put(route('admin.marketplace.settings.update'), [
            'enabled' => '1',
            'starter_rate_percent' => 15,
            'growth_rate_percent' => 10,
            'growth_threshold_amount' => 3_000_000,
        ])->assertRedirect();

        $this->assertTrue(MarketplaceSettings::enabled());
        $this->assertSame(0.15, MarketplaceSettings::defaultCommissionRate());
        $this->assertSame(0.10, MarketplaceSettings::growthCommissionRate());
        $this->assertSame(3_000_000.0, MarketplaceSettings::growthThresholdAmount());
        $this->assertTrue(AuditLog::query()->where('action', 'marketplace.settings_updated')->exists());
    }

    public function test_unchecking_enabled_masks_the_marketplace_again(): void
    {
        $this->enableMarketplace();
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.marketplace.settings.update'), [
            'starter_rate_percent' => 15,
            'growth_rate_percent' => 10,
            'growth_threshold_amount' => 3_000_000,
        ])->assertRedirect();

        $this->assertFalse(MarketplaceSettings::enabled());
    }

    public function test_non_admin_cannot_change_marketplace_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.marketplace.settings.update'), [
                'starter_rate_percent' => 15,
                'growth_rate_percent' => 10,
                'growth_threshold_amount' => 3_000_000,
            ])
            ->assertForbidden();
    }
}
