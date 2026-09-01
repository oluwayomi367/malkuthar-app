<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table ORDERS — panier pouvant être mixte (digital + physical + service),
 * cf. §20-§21. Le paiement doit être confirmé côté serveur via webhook/API
 * PSP avant d'être considéré comme définitif (§26).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Affilié à l'origine de la vente (tracking, §33-§39). Nullable si
            // aucun lien d'affiliation n'a généré la commande.
            $table->foreignUuid('referring_affiliate_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->string('order_number')->unique();

            $table->enum('status', [
                'pending', 'processing', 'completed', 'cancelled', 'refunded',
            ])->default('pending');

            // §26 - Confirmation des paiements (jamais fiable côté client seul)
            $table->enum('payment_status', [
                'pending', 'paid', 'failed', 'refunded', 'partially_refunded',
            ])->default('pending');

            $table->string('payment_method')->nullable()->comment('carte, mobile money, etc.');
            $table->string('payment_provider')->nullable()->comment('fedapay, cinetpay, ...');
            $table->string('payment_reference')->nullable()->unique()
                ->comment('Référence transaction PSP - utilisée pour idempotence (§27)');

            $table->string('currency', 3)->default('XOF');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('shipping_fee', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);

            // Contient un mélange DIGITAL/PHYSICAL/SERVICE -> flag pratique pour le checkout intelligent (§21)
            $table->boolean('has_digital_items')->default(false);
            $table->boolean('has_physical_items')->default(false);
            $table->boolean('has_service_items')->default(false);

            // Livraison physique (§13)
            $table->json('shipping_address')->nullable();
            $table->string('shipping_carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->enum('shipping_status', [
                'paid', 'preparing', 'shipped', 'in_delivery', 'delivered',
            ])->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('payment_status');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
