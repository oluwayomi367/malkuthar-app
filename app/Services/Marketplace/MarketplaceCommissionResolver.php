<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceCommissionRule;
use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\VendorProfile;
use App\Support\MarketplaceSettings;
use Illuminate\Support\Carbon;

/**
 * MARKETPLACE — point d'entrée UNIQUE pour connaître le taux de commission
 * plateforme à appliquer. Ordre de résolution :
 *
 *  0. l'override spécifique du produit (`marketplace_offers.commission_rate`),
 *     configuré par l'administrateur ;
 *  1. sinon l'override du vendeur (`vendor_profiles.commission_rate_override`) ;
 *  2. sinon la règle `marketplace_commission_rules` active couvrant la date,
 *     la plus spécifique (vendor > category > offer_type > platform) puis la
 *     plus prioritaire puis la plus récente ;
 *  3. sinon le barème de la catégorie (`marketplace_categories.commission_rate`),
 *     en remontant l'arbre jusqu'à trouver une valeur ;
 *  4. sinon le défaut plateforme (Setting `marketplace.default_commission_rate`).
 *
 * Le taux résolu est FIGÉ sur chaque sous-commande à l'achat : une règle
 * future ne réécrit jamais l'historique.
 */
class MarketplaceCommissionResolver
{
    /**
     * @return array{rate: float, rule: ?MarketplaceCommissionRule, source: string}
     */
    public function resolve(
        VendorProfile $vendor,
        MarketplaceCategory $category,
        string $offerType,
        ?Carbon $at = null,
        ?MarketplaceOffer $offer = null,
    ): array {
        $at ??= Carbon::now();

        if ($offer !== null && $offer->commission_rate !== null) {
            return [
                'rate' => (float) $offer->commission_rate,
                'rule' => null,
                'source' => 'offer_override',
            ];
        }

        if ($vendor->commission_rate_override !== null) {
            return [
                'rate' => (float) $vendor->commission_rate_override,
                'rule' => null,
                'source' => 'vendor_override',
            ];
        }

        $rule = $this->matchingRule($vendor, $category, $offerType, $at);

        if ($rule) {
            return ['rate' => (float) $rule->rate, 'rule' => $rule, 'source' => 'rule:'.$rule->scope];
        }

        $categoryRate = $this->categoryRate($category);

        if ($categoryRate !== null) {
            return ['rate' => $categoryRate, 'rule' => null, 'source' => 'category'];
        }

        return [
            'rate' => MarketplaceSettings::defaultCommissionRate(),
            'rule' => null,
            'source' => 'platform_default',
        ];
    }

    public function rate(
        VendorProfile $vendor,
        MarketplaceCategory $category,
        string $offerType,
        ?Carbon $at = null,
        ?MarketplaceOffer $offer = null,
    ): float {
        return $this->resolve($vendor, $category, $offerType, $at, $offer)['rate'];
    }

    /**
     * Calcule le montant de commission pour une base donnée, bornes de la
     * règle appliquées le cas échéant.
     *
     * @return array{rate: float, amount: float, rule_id: ?string, source: string}
     */
    public function computeFee(
        VendorProfile $vendor,
        MarketplaceCategory $category,
        string $offerType,
        float $base,
        ?Carbon $at = null,
        ?MarketplaceOffer $offer = null,
    ): array {
        $resolved = $this->resolve($vendor, $category, $offerType, $at, $offer);
        $amount = round($base * $resolved['rate'], 2);

        if ($resolved['rule']) {
            $amount = $resolved['rule']->clampFee($amount);
        }

        return [
            'rate' => $resolved['rate'],
            'amount' => $amount,
            'rule_id' => $resolved['rule']?->getKey(),
            'source' => $resolved['source'],
        ];
    }

    private function matchingRule(
        VendorProfile $vendor,
        MarketplaceCategory $category,
        string $offerType,
        Carbon $at,
    ): ?MarketplaceCommissionRule {
        $categoryIds = $category->lineageIds();

        $candidates = MarketplaceCommissionRule::query()
            ->active()
            ->coveringDate($at)
            ->where(function ($q) use ($vendor, $categoryIds, $offerType) {
                $q->where('scope', 'platform')
                    ->orWhere(fn ($q) => $q->where('scope', 'offer_type')->where('offer_type', $offerType))
                    ->orWhere(fn ($q) => $q->where('scope', 'category')->whereIn('category_id', $categoryIds))
                    ->orWhere(fn ($q) => $q->where('scope', 'vendor')->where('vendor_profile_id', $vendor->getKey()));
            })
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        // Profondeur de la catégorie ciblée : une règle sur la sous-catégorie
        // l'emporte sur une règle sur un ancêtre.
        $depthByCategory = MarketplaceCategory::query()
            ->whereIn('id', $candidates->pluck('category_id')->filter()->unique())
            ->pluck('depth', 'id');

        return $candidates
            ->sortByDesc(fn (MarketplaceCommissionRule $r) => [
                $r->scopeWeight(),
                $r->scope === 'category' ? ($depthByCategory[$r->category_id] ?? 0) : 0,
                $r->priority,
                $r->created_at?->getTimestamp() ?? 0,
            ])
            ->first();
    }

    private function categoryRate(MarketplaceCategory $category): ?float
    {
        if ($category->commission_rate !== null) {
            return (float) $category->commission_rate;
        }

        foreach ($category->ancestors()->reverse() as $ancestor) {
            if ($ancestor->commission_rate !== null) {
                return (float) $ancestor->commission_rate;
            }
        }

        return null;
    }
}
