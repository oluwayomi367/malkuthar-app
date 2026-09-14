<?php

namespace App\Http\Controllers;

use App\Services\CommissionRateResolver;
use Illuminate\View\View;

/**
 * §81 — Page « CONDITIONS DU PROGRAMME D'AFFILIATION MALKUTHAR ».
 *
 * Les valeurs chiffrées proviennent de config('malkuthar') pour rester
 * cohérentes avec le moteur. Le contenu doit être VALIDÉ JURIDIQUEMENT
 * avant publication (exigence §81) — cf. la bannière en tête de page.
 */
class AffiliateProgramTermsController extends Controller
{
    public function __invoke(CommissionRateResolver $rateResolver): View
    {
        // §27 — taux en vigueur (campagne éventuelle) plutôt que la config brute.
        $pct = $rateResolver->percentages();

        return view('legal.affiliate-program-terms', [
            'rateL1' => $pct[1],
            'rateL2' => $pct[2],
            'rateL3' => $pct[3],
            'validationDelayDays' => (int) config('malkuthar.affiliation.validation_delay_days', 14),
            'minWithdrawal' => (float) config('malkuthar.withdrawals.minimum_amount', 1000),
            'cooldownHours' => (int) config('malkuthar.withdrawals.cooldown_after_account_change_hours', 24),
        ]);
    }
}
