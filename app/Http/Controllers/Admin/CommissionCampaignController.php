<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionCampaign;
use App\Models\LedgerEntry;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\CommissionRateResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * §27 — taux de commission configurables. Les campagnes portent d'autres
 * jeux de taux (ex. B : 20 / 7 / 3), éventuellement bornés dans le temps.
 * Le résolveur choisit la campagne applicable ; à défaut, config / 15-10-5.
 */
class CommissionCampaignController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(CommissionRateResolver $resolver): View
    {
        return view('admin.commission-campaigns.index', [
            'campaigns' => CommissionCampaign::query()
                ->orderByDesc('is_default')
                ->orderByDesc('priority')
                ->orderByDesc('created_at')
                ->get(),
            'resolution' => $resolver->resolve(),
            // §42 — période de validation en vigueur.
            'validationDelayDays' => (int) Setting::get(
                'affiliation.validation_delay_days',
                config('malkuthar.affiliation.validation_delay_days', 14),
            ),
        ]);
    }

    /**
     * §42 — l'administrateur fixe le nombre de jours avant qu'une
     * commission devienne retirable.
     */
    public function updateValidationDelay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'validation_delay_days' => ['required', 'integer', 'min:0', 'max:365'],
        ]);

        Setting::put('affiliation.validation_delay_days', (int) $data['validation_delay_days']);

        $this->audit->record($request->user(), 'affiliation.validation_delay_updated', null,
            ['days' => (int) $data['validation_delay_days']],
            "Période de validation des commissions fixée à {$data['validation_delay_days']} jour(s)", $request);

        return back()->with('success', "Période de validation : {$data['validation_delay_days']} jour(s).");
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rate_l1' => ['required', 'numeric', 'min:0', 'max:100'],
            'rate_l2' => ['required', 'numeric', 'min:0', 'max:100'],
            'rate_l3' => ['required', 'numeric', 'min:0', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $campaign = CommissionCampaign::create([
            'name' => $data['name'],
            'rate_l1' => $data['rate_l1'] / 100,
            'rate_l2' => $data['rate_l2'] / 100,
            'rate_l3' => $data['rate_l3'] / 100,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'priority' => (int) ($data['priority'] ?? 0),
            'is_active' => true,
            'is_default' => $request->boolean('is_default'),
        ]);

        if ($campaign->is_default) {
            CommissionCampaign::query()->whereKeyNot($campaign->id)->update(['is_default' => false]);
        }

        $this->audit->record($request->user(), 'commission_campaign.created', $campaign, $data,
            "Campagne de taux « {$campaign->name} » créée", $request);

        return back()->with('success', "Campagne « {$campaign->name} » créée.");
    }

    public function update(Request $request, CommissionCampaign $campaign): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['activate', 'deactivate', 'make_default', 'clear_default'])],
        ]);

        match ($data['action']) {
            'activate' => $campaign->update(['is_active' => true]),
            'deactivate' => $campaign->update(['is_active' => false]),
            'clear_default' => $campaign->update(['is_default' => false]),
            'make_default' => tap($campaign->update(['is_default' => true, 'is_active' => true]), function () use ($campaign) {
                CommissionCampaign::query()->whereKeyNot($campaign->id)->update(['is_default' => false]);
            }),
        };

        $this->audit->record($request->user(), 'commission_campaign.updated', $campaign, $data,
            "Campagne « {$campaign->name} » : {$data['action']}", $request);

        return back()->with('success', 'Campagne mise à jour.');
    }

    public function destroy(Request $request, CommissionCampaign $campaign): RedirectResponse
    {
        // §27 — immuabilité : on ne supprime pas une campagne déjà utilisée
        // par des commissions (elles perdraient leur référence de campagne).
        if (LedgerEntry::query()->where('commission_campaign_id', $campaign->id)->exists()) {
            return back()->withErrors(['campaign' => 'Cette campagne a déjà servi à des commissions — désactivez-la plutôt que de la supprimer.']);
        }

        $name = $campaign->name;
        $campaign->delete();

        $this->audit->record($request->user(), 'commission_campaign.deleted', null, ['name' => $name],
            "Campagne de taux « {$name} » supprimée", $request);

        return back()->with('success', "Campagne « {$name} » supprimée.");
    }
}
