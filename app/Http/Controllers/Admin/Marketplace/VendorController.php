<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\VendorProfile;
use App\Services\Marketplace\VendorOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MARKETPLACE — gestion des comptes vendeurs en administration.
 */
class VendorController extends Controller
{
    public function __construct(private readonly VendorOnboardingService $onboarding) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $region = trim((string) $request->query('region', ''));

        $vendors = VendorProfile::query()
            ->with('user:id,first_name,last_name,email')
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('display_name', 'like', "%{$q}%")
                    ->orWhere('legal_name', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$q}%")
                        ->orWhere('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%"));
            }))
            ->when(in_array($status, ['pending', 'active', 'suspended', 'closed'], true),
                fn ($query) => $query->where('status', $status))
            ->when($region !== '', fn ($query) => $query->where('region', 'like', "%{$region}%"))
            ->orderByRaw("case status when 'pending' then 0 when 'active' then 1 when 'suspended' then 2 else 3 end")
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.marketplace.vendors.index', [
            'vendors' => $vendors,
            'q' => $q,
            'status' => $status,
            'region' => $region,
            'counts' => VendorProfile::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function show(VendorProfile $vendor): View
    {
        $vendor->load('user', 'documents.reviewer', 'approver');

        return view('admin.marketplace.vendors.show', ['vendor' => $vendor]);
    }

    public function approve(Request $request, VendorProfile $vendor): RedirectResponse
    {
        $this->onboarding->approve($vendor, $request->user());

        return back()->with('success', "Le compte vendeur « {$vendor->display_name} » est activé.");
    }

    public function reject(Request $request, VendorProfile $vendor): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $this->onboarding->reject($vendor, $request->user(), $data['reason']);

        return back()->with('success', 'La candidature a été refusée.');
    }

    public function suspend(Request $request, VendorProfile $vendor): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $this->onboarding->suspend($vendor, $request->user(), $data['reason']);

        return back()->with('success', "Le compte vendeur « {$vendor->display_name} » est suspendu.");
    }

    public function reactivate(Request $request, VendorProfile $vendor): RedirectResponse
    {
        $this->onboarding->reactivate($vendor, $request->user());

        return back()->with('success', "Le compte vendeur « {$vendor->display_name} » est réactivé.");
    }

    public function commission(Request $request, VendorProfile $vendor): RedirectResponse
    {
        $data = $request->validate([
            'commission_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:90'],
        ]);

        $rate = $data['commission_rate_percent'] === null || $data['commission_rate_percent'] === ''
            ? null
            : round((float) $data['commission_rate_percent'] / 100, 4);

        $this->onboarding->setCommissionOverride($vendor, $request->user(), $rate);

        return back()->with('success', 'Le taux de commission du vendeur a été mis à jour.');
    }
}
