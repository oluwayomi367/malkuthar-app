<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * AffiliateDashboardController — alimente resources/views/affiliate/dashboard.blade.php
 * avec les données réelles de l'affilié connecté (§43-§46 du cahier des charges).
 */
class AffiliateDashboardController extends Controller
{
    private const RATES = [1 => 15, 2 => 10, 3 => 5];

    public function index(Request $request): View
    {
        /** @var User $affiliate */
        $affiliate = $request->user();

        abort_unless($affiliate?->isAffiliate(), 403, "Cette page est réservée aux affiliés MALKUTHAR.");

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
            'team' => $this->buildTeamLevels($affiliate),
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

    /**
     * §46 - "Ma lignée" : pour chaque niveau (1 à 3), nombre de filleuls,
     * nombre d'actifs, et chiffre d'affaires généré par ce niveau.
     *
     * @return array<int, array{level: int, members: int, active: int, revenue: float, rate: int}>
     */
    private function buildTeamLevels(User $affiliate): array
    {
        $level1Ids = User::query()->where('parent_id', $affiliate->id)->pluck('id');
        $level2Ids = User::query()->whereIn('parent_id', $level1Ids)->pluck('id');
        $level3Ids = User::query()->whereIn('parent_id', $level2Ids)->pluck('id');

        $idsByLevel = [1 => $level1Ids, 2 => $level2Ids, 3 => $level3Ids];

        return collect($idsByLevel)
            ->map(fn (Collection $ids, int $level) => [
                'level' => $level,
                'members' => $ids->count(),
                'active' => $ids->isEmpty() ? 0 : User::query()
                    ->whereIn('id', $ids)
                    ->where('status', 'active')
                    ->count(),
                'revenue' => $ids->isEmpty() ? 0.0 : (float) Order::query()
                    ->whereIn('referring_affiliate_id', $ids)
                    ->where('payment_status', 'paid')
                    ->sum('total_amount'),
                'rate' => self::RATES[$level],
            ])
            ->values()
            ->all();
    }
}
