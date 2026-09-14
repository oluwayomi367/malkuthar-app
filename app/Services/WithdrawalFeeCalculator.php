<?php

namespace App\Services;

/**
 * §57 — Frais de retrait, configurables (gratuit / fixe / pourcentage).
 * L'administration ne doit jamais afficher "retrait gratuit" sans que ce
 * soit explicitement le type configuré (§57) : le calcul est centralisé
 * ici pour éviter toute divergence entre affichage et débit réel.
 */
class WithdrawalFeeCalculator
{
    public function compute(float $amount): float
    {
        $config = (array) config('malkuthar.withdrawals.fee', []);
        $type = $config['type'] ?? 'none';

        $fee = match ($type) {
            'fixed' => (float) ($config['fixed'] ?? 0),
            'percent' => round($amount * ((float) ($config['percent'] ?? 0) / 100), 2),
            default => 0.0,
        };

        // Le frais ne peut jamais rendre le montant net négatif.
        return min($fee, $amount);
    }
}
