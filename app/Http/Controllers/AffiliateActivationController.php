<?php

namespace App\Http\Controllers;

use App\Services\AffiliateOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * §30 — "DEVENIR AFFILIÉ" : un client active son espace affilié sans
 * créer de second compte.
 */
class AffiliateActivationController extends Controller
{
    public function __construct(private readonly AffiliateOnboardingService $onboarding) {}

    public function show(Request $request): RedirectResponse|View
    {
        if ($request->user()->isAffiliate()) {
            return redirect()->route('affiliate.dashboard');
        }

        return view('affiliate.join');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->onboarding->activate($request->user());

        return redirect()
            ->route('affiliate.dashboard')
            ->with('status', 'Votre espace affilié est activé. Voici votre lien personnel.');
    }
}
