<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\AffiliateOnboardingService;
use App\Services\ReferralResolver;
use App\Support\ProfileHome;
use App\Support\ReferralCookie;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly ReferralResolver $referralResolver,
        private readonly AffiliateOnboardingService $onboarding,
    ) {}

    public function create(Request $request): Response|View
    {
        return $this->respondWithReferralCookie('auth.register', $request);
    }

    public function createAffiliate(Request $request): Response|View
    {
        return $this->respondWithReferralCookie('auth.register-affiliate', $request);
    }

    private function respondWithReferralCookie(string $viewName, Request $request): Response|View
    {
        $code = $this->referralResolver->codeFromLinkOrCookie($request);
        if ($code && ! $request->hasCookie(ReferralCookie::NAME)) {
            $sponsor = $this->referralResolver->byCode($code);
            if ($sponsor) {
                return response()
                    ->view($viewName)
                    ->withCookie(cookie(
                        name: ReferralCookie::NAME,
                        value: $sponsor->code_parrainage,
                        minutes: ReferralCookie::minutes(),
                        httpOnly: true,
                    ));
            }
        }

        return view($viewName);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = $this->registerUser($request);

        return redirect()->intended(ProfileHome::url($user))
            ->with('status', 'Bienvenue chez MALKUTHAR ! Votre compte client est prêt.');
    }

    public function storeAffiliate(RegisterRequest $request): RedirectResponse
    {
        $user = $this->registerUser($request);

        return redirect()->route('affiliate.dashboard')
            ->with('status', 'Bienvenue dans le programme d\'affiliation MALKUTHAR ! Votre lien et votre tableau de bord sont prêts.');
    }

    private function registerUser(RegisterRequest $request): User
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

            // §35-§38 — rattachement définitif au parrain (ou Système par défaut).
            $this->onboarding->attributeSponsor($user, $sponsor);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return $user;
    }
}
