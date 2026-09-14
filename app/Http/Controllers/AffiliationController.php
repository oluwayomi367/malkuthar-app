<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CommissionRateResolver;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Page publique « Programme d'affiliation » — explique le FONCTIONNEMENT du
 * système (parcours, niveaux, cycle de vie des commissions, retraits, suivi).
 *
 * À ne pas confondre avec AffiliateProgramTermsController (§81) qui rend le
 * CONTRAT juridique (« Conditions du programme »). Cette page renvoie vers
 * celle-ci pour les détails contractuels.
 *
 * Les valeurs chiffrées viennent du moteur (CommissionRateResolver + config
 * malkuthar) pour rester cohérentes avec ce qui est réellement appliqué.
 */
class AffiliationController extends Controller
{
    public function __invoke(CommissionRateResolver $rateResolver): Response
    {
        // §27 — taux en vigueur (campagne éventuelle) plutôt que la config brute.
        $pct = $rateResolver->percentages();

        // Aperçu du catalogue affiliable : produits digitaux réels, pas une
        // maquette — le gain affiché (niveau 1) est calculé avec le même
        // taux que celui présenté juste au-dessus.
        $showcase = Product::query()
            ->affiliable()
            ->where('status', 'published')
            ->where('is_available', true)
            ->latest('created_at')
            ->take(4)
            ->get(['id', 'name', 'slug', 'main_image', 'price', 'promotional_price', 'currency'])
            ->map(function (Product $product) use ($pct) {
                $price = (float) ($product->promotional_price ?? $product->price);

                return [
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'image' => $product->main_image,
                    'price' => $price,
                    'currency' => $product->currency ?: 'FCFA',
                    'gain' => round($price * $pct[1] / 100, 2),
                ];
            });

        return Inertia::render('Affiliation', [
            'rateL1' => $pct[1],
            'rateL2' => $pct[2],
            'rateL3' => $pct[3],
            'rateTotal' => $pct[1] + $pct[2] + $pct[3],
            'paidLevels' => (int) config('malkuthar.affiliation.paid_levels', 3),
            'validationDelayDays' => (int) config('malkuthar.affiliation.validation_delay_days', 14),
            'referralCookieDays' => (int) config('malkuthar.affiliation.referral_cookie_days', 30),
            'referralPath' => (string) config('malkuthar.affiliation.referral_path', 'ref'),
            'minWithdrawal' => (float) config('malkuthar.withdrawals.minimum_amount', 1000),
            'cooldownHours' => (int) config('malkuthar.withdrawals.cooldown_after_account_change_hours', 24),
            'showcase' => $showcase,
        ]);
    }
}
