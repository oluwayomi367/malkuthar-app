<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MARKETPLACE — barème de commission plateforme configurable.
 *
 * Règles datées et hiérarchisées. App\Services\Marketplace\MarketplaceCommissionResolver
 * choisit la plus spécifique et la plus prioritaire couvrant la date, sinon
 * le défaut plateforme (Setting `marketplace.default_commission_rate`).
 * Le taux résolu est FIGÉ sur chaque sous-commande à l'achat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_commission_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->enum('scope', ['platform', 'offer_type', 'category', 'vendor']);
            $table->foreignUuid('category_id')->nullable()->constrained('marketplace_categories')->cascadeOnDelete();
            $table->foreignUuid('vendor_profile_id')->nullable()->constrained('vendor_profiles')->cascadeOnDelete();
            $table->string('offer_type')->nullable();

            $table->decimal('rate', 6, 4); // fraction
            $table->decimal('min_fee', 14, 2)->nullable();
            $table->decimal('max_fee', 14, 2)->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['is_active', 'scope']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_commission_rules');
    }
};
