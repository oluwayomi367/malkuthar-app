<?php

namespace App\Services;

use App\Models\CommissionCampaign;
use Illuminate\Support\Carbon;

/**
 * §27 — point d'entrée UNIQUE pour connaître les taux de commission à
 * appliquer. Ordre de résolution :
 *
 *  1. campagne active couvrant la date (priorité la plus haute, puis la
 *     plus récente) ;
 *  2. sinon la campagne marquée « par défaut » ;
 *  3. sinon config('malkuthar.affiliation.commission_rates') ;
 *  4. sinon les taux standards 15 / 10 / 5 %.
 *
 * Ajouter une campagne = insérer une ligne : aucun autre code à toucher.
 */
class CommissionRateResolver
{
    public const DEFAULT_RATES = [1 => 0.15, 2 => 0.10, 3 => 0.05];

    /**
     * @return array{campaign: ?CommissionCampaign, rates: array<int, float>}
     */
    public function resolve(?Carbon $at = null): array
    {
        $at ??= Carbon::now();

        $campaign = CommissionCampaign::query()
            ->coveringDate($at)
            ->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->first();

        $campaign ??= CommissionCampaign::query()->where('is_default', true)->first();

        if ($campaign) {
            return ['campaign' => $campaign, 'rates' => $campaign->rates()];
        }

        $cfg = (array) config('malkuthar.affiliation.commission_rates', []);

        return [
            'campaign' => null,
            'rates' => [
                1 => (float) ($cfg[1] ?? self::DEFAULT_RATES[1]),
                2 => (float) ($cfg[2] ?? self::DEFAULT_RATES[2]),
                3 => (float) ($cfg[3] ?? self::DEFAULT_RATES[3]),
            ],
        ];
    }

    /**
     * @return array<int, float>
     */
    public function rates(?Carbon $at = null): array
    {
        return $this->resolve($at)['rates'];
    }

    /**
     * @return array<int, int> taux en pourcentage entier, pour l'affichage.
     */
    public function percentages(?Carbon $at = null): array
    {
        return array_map(static fn ($r) => (int) round($r * 100), $this->rates($at));
    }
}
