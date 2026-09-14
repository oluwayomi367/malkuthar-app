<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table COUPONS (§79) + colonnes de remise sur les commandes.
 *
 * RÈGLE §79 : la remise réduit la BASE DE CALCUL de la commission d'un
 * produit digital affiliable. C'est order_items.commission_base_amount
 * qui est diminué (= total_price - discount_amount) par CheckoutService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->enum('type', ['percent', 'fixed']);
            $table->decimal('value', 14, 2); // % si percent, montant si fixed

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);

            $table->decimal('min_order_amount', 14, 2)->nullable();
            $table->json('product_ids')->nullable();   // null = tous les produits
            $table->json('categories')->nullable();    // null = toutes les catégories

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignUuid('coupon_id')->nullable()->after('referring_affiliate_id')
                ->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code')->nullable()->after('coupon_id');
            $table->decimal('discount_total', 14, 2)->default(0)->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['coupon_code', 'discount_total']);
        });

        Schema::dropIfExists('coupons');
    }
};
