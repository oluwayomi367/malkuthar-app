<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Services\CommissionRateResolver;
use Illuminate\Support\Collection;

/**
 * AffiliateLineageService — §46/§61 : lignée sur 3 niveaux d'un affilié,
 * utilisée par le dashboard affilié ET la fiche affilié en administration.
 */
class AffiliateLineageService
{
    public function __construct(
        private readonly CommissionRateResolver $rateResolver = new CommissionRateResolver,
    ) {}

    /**
     * @return array<int, array{level: int, members: int, active: int, sales: int, revenue: float, rate: int}>
     */
    public function levels(User $affiliate): array
    {
        $level1Ids = User::query()->where('parent_id', $affiliate->id)->pluck('id');
        $level2Ids = User::query()->whereIn('parent_id', $level1Ids)->pluck('id');
        $level3Ids = User::query()->whereIn('parent_id', $level2Ids)->pluck('id');

        $idsByLevel = [1 => $level1Ids, 2 => $level2Ids, 3 => $level3Ids];

        // §27 - taux en vigueur (campagne éventuelle), en pourcentage entier.
        $percentages = $this->rateResolver->percentages();

        return collect($idsByLevel)
            ->map(function (Collection $ids, int $level) use ($percentages) {
                // §46 - un seul balayage des commandes payées de ce niveau :
                // nombre de ventes réalisées + chiffre d'affaires généré.
                // Aucune donnée personnelle n'est exposée, seulement des agrégats.
                $agg = $ids->isEmpty()
                    ? null
                    : Order::query()
                        ->whereIn('referring_affiliate_id', $ids)
                        ->where('payment_status', 'paid')
                        ->selectRaw('COUNT(*) as sales, COALESCE(SUM(total_amount), 0) as revenue')
                        ->first();

                return [
                    'level' => $level,
                    'members' => $ids->count(),
                    'active' => $ids->isEmpty() ? 0 : User::query()
                        ->whereIn('id', $ids)
                        ->where('status', 'active')
                        ->count(),
                    'sales' => (int) ($agg->sales ?? 0),
                    'revenue' => (float) ($agg->revenue ?? 0),
                    'rate' => $percentages[$level] ?? 0,
                ];
            })
            ->values()
            ->all();
    }
}
