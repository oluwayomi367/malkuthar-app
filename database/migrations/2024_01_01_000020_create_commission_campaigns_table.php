<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §27 — taux de commission configurables. Les taux standards restent
 * 15 / 10 / 5 % (config / défaut), mais l'administration peut créer des
 * campagnes avec d'autres taux, éventuellement bornées dans le temps.
 * Le taux réellement appliqué est figé sur chaque écriture de commission
 * (`ledger_entries.commission_rate`) : une campagne future ne réécrit
 * jamais l'historique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');

            // Taux stockés sous forme de fraction (0.15 = 15 %).
            $table->decimal('rate_l1', 6, 4);
            $table->decimal('rate_l2', 6, 4);
            $table->decimal('rate_l3', 6, 4);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('priority')->default(0);

            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->decimal('commission_rate', 6, 4)->nullable()->after('level');
            $table->foreignUuid('commission_campaign_id')->nullable()->after('commission_rate')
                ->constrained('commission_campaigns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('commission_campaign_id');
            $table->dropColumn('commission_rate');
        });
        Schema::dropIfExists('commission_campaigns');
    }
};
