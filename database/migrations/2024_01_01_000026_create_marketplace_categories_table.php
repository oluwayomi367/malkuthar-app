<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MARKETPLACE — arbre de catégories / sous-catégories à profondeur libre
 * (liste d'adjacence + chemin matérialisé `path` pour les requêtes de
 * sous-arbre sans récursion).
 *
 * Chaque catégorie configure les types d'offres admis, un barème de
 * commission optionnel, et un jeu de règles (`ruleset`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->nullable()->constrained('marketplace_categories')->nullOnDelete();

            // Chemin matérialisé « /{id}/{id}/… » et cache de profondeur.
            $table->string('path')->default('');
            $table->unsignedTinyInteger('depth')->default(0);

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('image_path')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            // Sous-ensemble de ['physical','digital','service','training'].
            $table->json('applicable_offer_types');

            // Barème de commission propre à la catégorie (fraction).
            $table->decimal('commission_rate', 6, 4)->nullable();

            // require_approval, min_price, max_price, max_images,
            // requires_shipping, allow_variants, …
            $table->json('ruleset')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['parent_id', 'slug']);
            $table->index(['is_active', 'position']);
            $table->index('path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_categories');
    }
};
