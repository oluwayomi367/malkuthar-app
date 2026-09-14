<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MARKETPLACE — variantes d'une offre physique (couleur, taille, modèle…),
 * chacune avec son SKU, son stock, un prix optionnel et une image.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_offer_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('offer_id')->constrained('marketplace_offers')->cascadeOnDelete();

            $table->string('name');                       // « Rouge / M »
            $table->json('option_values');                // {"couleur":"Rouge","taille":"M"}
            $table->string('sku')->nullable()->unique();

            $table->decimal('price_override', 14, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->integer('stock_reserved')->default(0);

            $table->string('image_path')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['offer_id', 'is_active']);
        });

        Schema::table('marketplace_order_items', function (Blueprint $table) {
            $table->foreignUuid('offer_variant_id')->nullable()->after('offer_id')
                ->constrained('marketplace_offer_variants')->nullOnDelete();
            $table->string('variant_label')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offer_variant_id');
            $table->dropColumn('variant_label');
        });
        Schema::dropIfExists('marketplace_offer_variants');
    }
};
