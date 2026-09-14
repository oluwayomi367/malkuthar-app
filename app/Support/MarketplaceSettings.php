<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Accès unifié aux paramètres du module Marketplace : la table `settings`
 * l'emporte, sinon `config('marketplace.*')`.
 */
class MarketplaceSettings
{
    public static function enabled(): bool
    {
        return self::bool('marketplace.enabled', (bool) config('marketplace.enabled'));
    }

    public static function defaultCommissionRate(): float
    {
        return (float) Setting::get(
            'marketplace.default_commission_rate',
            config('marketplace.commission.default_rate'),
        );
    }

    public static function settlementHoldDays(): int
    {
        return (int) Setting::get(
            'marketplace.settlement_hold_days',
            config('marketplace.settlement.hold_days'),
        );
    }

    public static function minPayoutAmount(): float
    {
        return (float) Setting::get(
            'marketplace.min_payout_amount',
            config('marketplace.settlement.min_payout_amount'),
        );
    }

    public static function payoutSchedule(): string
    {
        return (string) Setting::get(
            'marketplace.payout_schedule',
            config('marketplace.settlement.payout_schedule'),
        );
    }

    public static function payoutAccountCooldownHours(): int
    {
        return (int) Setting::get(
            'marketplace.payout_account_cooldown_hours',
            config('marketplace.settlement.payout_account_cooldown_hours'),
        );
    }

    public static function vendorSelfPublish(): bool
    {
        return self::bool(
            'marketplace.vendor_self_publish',
            (bool) config('marketplace.offers.vendor_self_publish'),
        );
    }

    public static function autoApproveTrustedVendors(): bool
    {
        return self::bool(
            'marketplace.auto_approve_trusted_vendors',
            (bool) config('marketplace.offers.auto_approve_trusted_vendors'),
        );
    }

    public static function maxGalleryImages(): int
    {
        return (int) Setting::get(
            'marketplace.max_gallery_images',
            config('marketplace.offers.max_gallery_images'),
        );
    }

    private static function bool(string $key, bool $default): bool
    {
        $value = Setting::get($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
