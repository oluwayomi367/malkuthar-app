<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VendorRegisterRequest;
use App\Models\User;
use App\Services\AffiliateOnboardingService;
use App\Services\Marketplace\VendorOnboardingService;
use App\Services\ReferralResolver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * MARKETPLACE — Inscription publique dédiée aux vendeurs : création de compte
 * utilisateur et soumission du profil boutique en une étape avec validation
 * manuelle obligatoire par l'administration.
 */
class VendorRegistrationController extends Controller
{
    public function __construct(
        private readonly ReferralResolver $referralResolver,
        private readonly AffiliateOnboardingService $affiliateOnboarding,
        private readonly VendorOnboardingService $vendorOnboarding,
    ) {}

    public function create(Request $request): \Illuminate\Http\Response|View|RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $profile = $user->vendorProfile;
            if ($profile) {
                return redirect()->route($profile->status === 'active' ? 'vendor.dashboard' : 'vendor.application.status');
            }

            return redirect()->route('vendor.application');
        }

        $code = $this->referralResolver->codeFromLinkOrCookie($request);
        if ($code && ! $request->hasCookie(\App\Support\ReferralCookie::NAME)) {
            $sponsor = $this->referralResolver->byCode($code);
            if ($sponsor) {
                return response()
                    ->view('auth.register-vendor')
                    ->withCookie(cookie(
                        name: \App\Support\ReferralCookie::NAME,
                        value: $sponsor->code_parrainage,
                        minutes: \App\Support\ReferralCookie::minutes(),
                        httpOnly: true,
                    ));
            }
        }

        return view('auth.register-vendor');
    }

    public function store(VendorRegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $sponsor = $this->referralResolver->fromRequest($request, $data['referral_code'] ?? null);

        $user = DB::transaction(function () use ($data, $sponsor) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? null,
                'currency' => $data['currency'] ?? 'XOF',
                'role' => 'client',
                'status' => 'active',
            ]);

            // Rattachement affiliation (parrain direct ou Système par défaut)
            $this->affiliateOnboarding->attributeSponsor($user, $sponsor);

            // Création du profil vendeur en statut "pending" (modération manuelle obligatoire)
            $this->vendorOnboarding->submitApplication($user, [
                'display_name' => $data['display_name'],
                'legal_name' => $data['legal_name'] ?? null,
                'bio' => $data['bio'] ?? null,
                'region' => $data['region'] ?? null,
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
                'contact_email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'payout_currency' => $data['payout_currency'] ?? $user->currency ?? 'XOF',
                'metadata' => [
                    'sector' => $data['business_sector'] ?? 'fashion_beauty',
                ],
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('vendor.application.status')
            ->with('success', 'Bienvenue chez MALKUTHAR ! Votre compte est créé et votre demande d\'ouverture de boutique a été enregistrée. Notre équipe valide manuellement chaque candidature sous 24 à 48h.');
    }
}
