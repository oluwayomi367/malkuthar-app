<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Services\AffiliateLineageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AffiliateDashboardController — alimente resources/views/affiliate/dashboard.blade.php
 * avec les données réelles de l'affilié connecté (§43-§46 du cahier des charges).
 */
class AffiliateDashboardController extends Controller
{
    public function __construct(private readonly AffiliateLineageService $lineage) {}

    public function index(Request $request): View
    {
        /** @var User $affiliate */
        $affiliate = $request->user();

        abort_unless($affiliate?->isAffiliate(), 403, 'Cette page est réservée aux affiliés MALKUTHAR.');

        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $affiliate->id],
            ['currency' => $affiliate->currency ?? 'XOF']
        );

        return view('affiliate.dashboard', [
            'affiliate' => $affiliate,
            'wallet' => [
                'balance_available' => (float) $wallet->balance_available,
                'balance_pending' => (float) $wallet->balance_pending,
                'total_earned' => (float) $wallet->total_earned,
                'currency' => $wallet->currency,
            ],
            'referralLink' => $this->buildReferralLink($affiliate),
            'team' => $this->lineage->levels($affiliate),
        ]);
    }

    /**
     * §33 - lien personnel d'affiliation, unique et permanent.
     */
    private function buildReferralLink(User $affiliate): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return "{$baseUrl}/ref/{$affiliate->code_parrainage}";
    }
}
