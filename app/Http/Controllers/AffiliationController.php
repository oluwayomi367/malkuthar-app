<?php

namespace App\Http\Controllers;

use App\Services\CommissionRateResolver;
use Illuminate\View\View;

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
    public function __invoke(CommissionRateResolver $rateResolver): View
    {
        // §27 — taux en vigueur (campagne éventuelle) plutôt que la config brute.
        $pct = $rateResolver->percentages();

        return view('storefront.affiliation', [
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
        ]);
    }
}
