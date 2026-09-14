<?php

namespace App\Console\Commands;

use App\Services\AffiliationService;
use Illuminate\Console\Command;

/**
 * §40-§42 — Rend disponibles les commissions dont la période de
 * validation est écoulée. À planifier (voir bootstrap/app.php).
 */
class ReleaseAffiliateCommissions extends Command
{
    protected $signature = 'affiliate:release-commissions {--batch=1000 : Nombre maximum de commissions traitées par exécution}';

    protected $description = 'Fait passer les commissions PENDING échues au statut AVAILABLE et met à jour les portefeuilles.';

    public function handle(AffiliationService $affiliation): int
    {
        $count = $affiliation->releaseDueCommissions((int) $this->option('batch'));

        $this->info("{$count} commission(s) rendue(s) disponible(s).");

        return self::SUCCESS;
    }
}
