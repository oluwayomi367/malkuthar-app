<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §11-§12 — variantes des produits physiques (taille, couleur, modèle,
 * format…), chacune avec son SKU / stock / prix / image ; et compteur de
 * « stock vendu » sur produits + variantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();

            $table->string('name');                       // libellé lisible : « Rouge / M »
            $table->json('options');                      // {"couleur":"Rouge","taille":"M"}
            $table->string('sku')->nullable()->unique();

            $table->decimal('price', 14, 2)->nullable();  // null = prix du produit
            $table->integer('stock_available')->default(0);
            $table->integer('stock_reserved')->default(0);
            $table->integer('stock_sold')->default(0);

            $table->string('image')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'is_active']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock_sold')->default(0)->after('stock_reserved');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignUuid('product_variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->nullOnDelete();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignUuid('product_variant_id')->nullable()->after('product_id')
                ->constrained('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stock_sold');
        });
        Schema::dropIfExists('product_variants');
    }
};
