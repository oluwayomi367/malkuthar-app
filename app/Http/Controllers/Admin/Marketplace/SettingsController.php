<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\VendorProfile;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Support\MarketplaceSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MARKETPLACE — réglages d'exploitation : activation du module et paliers
 * de commission affichés sur la page publique « Tarifs vendeur »
 * (voir config/marketplace.php). Tout passe par `Setting` (§ pattern déjà
 * utilisé pour la période de validation des commissions d'affiliation) —
 * jamais d'édition directe du fichier de config en production.
 */
class SettingsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.marketplace.settings.index', [
            'enabled' => MarketplaceSettings::enabled(),
            'starterRate' => MarketplaceSettings::defaultCommissionRate(),
            'growthRate' => MarketplaceSettings::growthCommissionRate(),
            'growthThreshold' => MarketplaceSettings::growthThresholdAmount(),
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
}
