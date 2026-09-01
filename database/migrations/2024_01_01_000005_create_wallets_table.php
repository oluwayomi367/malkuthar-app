<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table WALLETS — portefeuille de l'affilié (§43-§44).
 *
 * IMPORTANT (§48-§49) : les colonnes de solde ci-dessous sont des CACHES
 * dénormalisés à but de performance (affichage dashboard). La source de
 * vérité reste le ledger (table ledger_entries). Toute écriture sur ces
 * colonnes doit obligatoirement passer par un Service métier encapsulé
 * dans DB::transaction() et accompagné d'une ligne de ledger correspondante
 * (jamais un simple `$wallet->balance = X; $wallet->save();` isolé).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();

            $table->string('currency', 3)->default('XOF');

            // Soldes en cache, recalculables à tout moment depuis le ledger
            $table->decimal('balance_available', 14, 2)->default(0)->comment('§43 - Retirable');
            $table->decimal('balance_pending', 14, 2)->default(0)->comment('§40 - Commissions en attente');
            $table->decimal('total_earned', 14, 2)->default(0)->comment('§43 - Total historique validé');
            $table->decimal('total_withdrawn', 14, 2)->default(0)->comment('§43 - Déjà transféré');

            // Coordonnées de retrait par défaut (§52-§53), l'historique/preuves
            // sensibles restant dans une table dédiée si besoin plus tard.
            $table->string('default_withdrawal_method')->nullable();
            $table->json('default_withdrawal_account')->nullable();

            $table->timestamp('last_reconciled_at')->nullable()
                ->comment('Dernière vérification balance = SUM(ledger_entries)');

            $table->timestamps();

            $table->index('balance_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
