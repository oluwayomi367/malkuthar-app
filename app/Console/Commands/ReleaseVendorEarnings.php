<?php

namespace App\Console\Commands;

use App\Services\Marketplace\VendorSettlementService;
use Illuminate\Console\Command;

/**
 * MARKETPLACE — bascule les crédits de vente échus (fenêtre de rétractation
 * écoulée) de PENDING à AVAILABLE. À planifier (voir bootstrap/app.php).
 * Miroir de `affiliate:release-commissions`.
 */
class ReleaseVendorEarnings extends Command
{
    protected $signature = 'marketplace:release-earnings';

    protected $description = 'Rend disponibles les revenus vendeurs dont la période de validation est écoulée.';

    public function handle(VendorSettlementService $settlement): int
    {
        $count = $settlement->releaseDue();

        $this->info("{$count} revenu(s) vendeur rendu(s) disponible(s).");

        return self::SUCCESS;
    }
}
