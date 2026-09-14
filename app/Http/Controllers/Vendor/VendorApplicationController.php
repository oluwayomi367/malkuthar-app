<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\VendorApplicationRequest;
use App\Services\Marketplace\VendorOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MARKETPLACE — « Devenir vendeur » : un compte MALKUTHAR demande l'ouverture
 * d'un espace vendeur. Pas de second compte.
 */
class VendorApplicationController extends Controller
{
    public function __construct(private readonly VendorOnboardingService $onboarding) {}

    public function show(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->vendorProfile;

        if ($profile) {
            return redirect()->route($profile->status === 'active' ? 'vendor.dashboard' : 'vendor.application.status');
        }

        return view('vendor.application');
    }

    public function store(VendorApplicationRequest $request): RedirectResponse
    {
        $this->onboarding->submitApplication($request->user(), $request->validated());

        return redirect()
            ->route('vendor.application.status')
            ->with('success', 'Votre demande d\'espace vendeur a bien été envoyée. Nous revenons vers vous après vérification.');
    }

    public function status(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->vendorProfile;

        if ($profile === null) {
            return redirect()->route('vendor.application');
        }

        if ($profile->status === 'active') {
            return redirect()->route('vendor.dashboard');
        }

        return view('vendor.application-status', ['profile' => $profile]);
    }
}
