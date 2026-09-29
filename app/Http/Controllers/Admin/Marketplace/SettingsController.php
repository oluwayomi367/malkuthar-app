<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\VendorProfile;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Support\MarketplaceSettings;
use App\Support\StorefrontSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MARKETPLACE & VITRINE — réglages d'exploitation : bandeau d'annonce défilant,
 * activation du module Marketplace et paliers de commission affichés sur la page
 * publique « Tarifs vendeur » (voir config/marketplace.php).
 * Tout passe par `Setting` — jamais d'édition directe de config en production.
 */
class SettingsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): \Inertia\Response
    {
        return \Inertia\Inertia::render('Admin/Marketplace/Settings/Index', [
            'heading' => 'Réglages Vitrine & Marketplace',
            'eyebrow' => 'Place de marché',
            'enabled' => MarketplaceSettings::enabled(),
            'starterRate' => MarketplaceSettings::defaultCommissionRate(),
            'growthRate' => MarketplaceSettings::growthCommissionRate(),
            'growthThreshold' => MarketplaceSettings::growthThresholdAmount(),
            'announcementTicker' => StorefrontSettings::announcementTicker(),
            'announcementTickerEnabled' => StorefrontSettings::announcementTickerEnabled(),
            // Vendeurs dont l'override manuel diffère du taux Starter par défaut.
            'vendorsWithOverride' => VendorProfile::query()
                ->whereNotNull('commission_rate_override')
                ->with('user:id,first_name,last_name')
                ->orderByDesc('updated_at')
                ->get(['id', 'user_id', 'display_name', 'commission_rate_override', 'updated_at']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'starter_rate_percent' => ['required', 'numeric', 'min:0', 'max:90'],
            'growth_rate_percent' => ['required', 'numeric', 'min:0', 'max:90'],
            'growth_threshold_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $enabled = $request->boolean('enabled');
        Setting::put('marketplace.enabled', $enabled);
        Setting::put('marketplace.default_commission_rate', round($data['starter_rate_percent'] / 100, 4));
        Setting::put('marketplace.growth_commission_rate', round($data['growth_rate_percent'] / 100, 4));
        Setting::put('marketplace.growth_threshold_amount', (float) $data['growth_threshold_amount']);

        $this->audit->record($request->user(), 'marketplace.settings_updated', null, [
            'enabled' => $enabled,
            'starter_rate' => $data['starter_rate_percent'],
            'growth_rate' => $data['growth_rate_percent'],
            'growth_threshold' => $data['growth_threshold_amount'],
        ], 'Réglages de la place de marché mis à jour', $request);

        return back()->with('success', $enabled
            ? 'La place de marché est désormais accessible au public.'
            : 'Réglages enregistrés — la place de marché reste masquée au public.');
    }

    public function updateAnnouncement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'announcement_ticker' => ['nullable', 'string', 'max:2000'],
            'announcement_ticker_enabled' => ['nullable', 'boolean'],
        ]);

        $enabled = $request->boolean('announcement_ticker_enabled');
        $text = trim((string) ($data['announcement_ticker'] ?? ''));

        Setting::put('storefront.announcement_ticker_enabled', $enabled);
        Setting::put('storefront.announcement_ticker', $text);

        $this->audit->record($request->user(), 'storefront.announcement_updated', null, [
            'enabled' => $enabled,
            'ticker' => $text,
        ], 'Bandeau d\'annonce vitrine mis à jour', $request);

        return back()->with('success', 'Le bandeau d\'annonce défilant a été mis à jour avec succès.');
    }
}
