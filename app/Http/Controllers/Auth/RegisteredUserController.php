<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\AffiliateOnboardingService;
use App\Services\ReferralResolver;
use App\Support\ProfileHome;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly ReferralResolver $referralResolver,
        private readonly AffiliateOnboardingService $onboarding,
    ) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // §35 — parrain issu du formulaire OU du cookie /ref/{code}.
        $sponsor = $this->referralResolver->fromRequest($request, $data['referral_code'] ?? null);

        $user = DB::transaction(function () use ($data, $sponsor) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $data['password'], // hashé par le cast 'hashed'
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? null,
                'currency' => $data['currency'] ?? 'XOF',
                'role' => 'client',
                'status' => 'active',
            ]);

            // §35-§38 — rattachement définitif au parrain (no-op si interdit).
            $this->onboarding->attributeSponsor($user, $sponsor);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->intended(ProfileHome::url($user))
            ->with('status', 'Bienvenue chez MALKUTHAR ! Votre compte est prêt.');
    }
}
