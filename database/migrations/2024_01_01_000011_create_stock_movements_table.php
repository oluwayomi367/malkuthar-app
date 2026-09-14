<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table STOCK_MOVEMENTS — historique des mouvements de stock (§11 :
 * "conserver l'historique des mouvements de stock").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignUuid('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignUuid('admin_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('type', [
                'reserved',   // §11 - commande créée, stock réservé
                'released',   // réservation libérée (commande annulée/expirée)
                'sold',       // paiement confirmé, réservation convertie en vente
                'returned',   // §80 - remboursement, remise en stock
                'restocked',  // §11 - réapprovisionnement manuel
                'adjustment', // correction manuelle (inventaire)
            ]);

            $table->integer('quantity'); // toujours positif, le sens est donné par `type`
            $table->string('variant_sku')->nullable();
            $table->text('reason')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
