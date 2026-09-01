<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table PRODUCTS — un produit peut être DIGITAL, PHYSICAL ou SERVICE (§6).
 * RÈGLE ABSOLUE (§4) : seul un produit DIGITAL peut être affiliable
 * (is_affiliable). Cette contrainte métier doit être vérifiée en Service/
 * Form Request, la migration ne fait que stocker le champ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // §6 - Type de produit
            $table->enum('type', ['digital', 'physical', 'service']);

            // §8 - Affiliation activée: OUI/NON (uniquement pertinent si type = digital)
            $table->boolean('is_affiliable')->default(false);

            // Prix (§7, §10, §16)
            $table->decimal('price', 14, 2);
            $table->decimal('promotional_price', 14, 2)->nullable();
            $table->string('currency', 3)->default('XOF');

            // Champs communs
            $table->string('category')->nullable();
            $table->string('sub_category')->nullable();
            $table->string('main_image')->nullable();
            $table->json('gallery')->nullable();

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_available')->default(true);

            // --- Spécifique DIGITAL (§7, §9) ---
            $table->string('file_path')->nullable()->comment('Fichier protégé, livraison automatique');
            $table->string('presentation_video')->nullable();

            // --- Spécifique PHYSICAL (§10-§12) ---
            $table->string('sku')->nullable()->unique();
            $table->integer('stock_available')->nullable()->default(0);
            $table->integer('stock_reserved')->nullable()->default(0);
            $table->decimal('weight', 10, 2)->nullable();
            $table->json('dimensions')->nullable();

            // --- Spécifique SERVICE (§16-§18) ---
            $table->integer('duration_minutes')->nullable();
            $table->string('location')->nullable();
            $table->string('delivery_mode')->nullable()->comment('présentiel / visioconférence');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_affiliable']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
