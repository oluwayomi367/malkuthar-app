<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\VendorProfile;
use App\Models\Setting;
use App\Models\User;

/**
 * Helpers partagés par les tests du module Marketplace.
 */
trait InteractsWithMarketplace
{
    protected function enableMarketplace(): void
    {
        Setting::put('marketplace.enabled', '1');
    }

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    protected function activeVendor(array $attributes = []): VendorProfile
    {
        return VendorProfile::factory()->active()->create($attributes);
    }
}
