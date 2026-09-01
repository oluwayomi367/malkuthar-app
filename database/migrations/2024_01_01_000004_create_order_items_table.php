<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table ORDER_ITEMS — une ligne de commande. On snapshot le type de produit
 * et l'éligibilité à l'affiliation AU MOMENT DE L'ACHAT (is_affiliable_snapshot),
 * car ces valeurs ne doivent jamais changer rétroactivement une commande déjà
 * passée, même si l'admin modifie le produit plus tard.
 *
 * RÈGLE ABSOLUE (§4, §5, §80) : seul commission_base_amount des lignes
 * DIGITAL + is_affiliable_snapshot=true sert de base au calcul des commissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->restrictOnDelete();

            // Snapshots figés au moment de la commande
            $table->string('product_name');
            $table->enum('product_type', ['digital', 'physical', 'service']);
            $table->boolean('is_affiliable_snapshot')->default(false);

            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_price', 14, 2);
            $table->string('currency', 3)->default('XOF');

            // Montant réellement éligible au calcul des commissions (§5, §79)
            // = total_price - discount_amount, uniquement si product_type=digital
            // ET is_affiliable_snapshot=true, sinon 0/null.
            $table->decimal('commission_base_amount', 14, 2)->nullable();

            // Variante produit physique éventuelle (§12)
            $table->string('variant_sku')->nullable();
            $table->json('variant_options')->nullable();

            // Réservation service (§17)
            $table->timestamp('reservation_datetime')->nullable();

            $table->timestamps();

            $table->index(['product_type', 'is_affiliable_snapshot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
