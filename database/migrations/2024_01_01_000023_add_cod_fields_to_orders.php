<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paiement à la livraison (Cash On Delivery) — §22/§26.
 *
 * Le COD n'est pas un PSP : la commande naît `payment_status=pending`,
 * `payment_provider='cod'`, et c'est l'administrateur qui déclare
 * l'encaissement depuis la section Commandes. Ces deux colonnes tracent
 * QUI a encaissé et QUAND (le reste du cycle de vie s'appuie sur les
 * colonnes déjà présentes : `status`, `shipping_status`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('cod_collected_at')->nullable()->after('payment_reference');
            $table->foreignUuid('cod_collected_by')->nullable()->after('cod_collected_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cod_collected_by');
            $table->dropColumn('cod_collected_at');
        });
    }
};
