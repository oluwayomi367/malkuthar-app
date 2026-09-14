<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MARKETPLACE — commandes de la place de marché.
 *
 * Un panier multi-vendeurs devient : 1 `marketplace_order` (vue acheteur)
 * scindé en N `marketplace_suborders` (une par vendeur — unité de règlement
 * ET de traitement logistique). Le taux de commission est FIGÉ sur la
 * sous-commande à l'achat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number')->unique();
            $table->foreignUuid('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('buyer_email');
            $table->string('buyer_phone')->nullable();

            $table->char('currency', 3)->default('XOF');
            $table->decimal('items_total', 14, 2)->default(0);
            $table->decimal('shipping_total', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);

            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded', 'partially_refunded'])->default('pending');
            $table->string('payment_provider')->nullable();
            $table->string('payment_reference')->nullable()->unique();
            $table->timestamp('paid_at')->nullable();

            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->json('shipping_address')->nullable();
            $table->json('utm')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('payment_status');
            $table->index('status');
        });

        Schema::create('marketplace_suborders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('suborder_number')->unique();
            $table->foreignUuid('marketplace_order_id')->constrained('marketplace_orders')->cascadeOnDelete();
            $table->foreignUuid('vendor_profile_id')->constrained('vendor_profiles')->restrictOnDelete();

            $table->decimal('items_total', 14, 2)->default(0);
            $table->decimal('shipping_total', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('vendor_gross', 14, 2)->default(0);

            $table->foreignUuid('commission_rule_id')->nullable()
                ->constrained('marketplace_commission_rules')->nullOnDelete();
            $table->decimal('commission_rate', 6, 4)->default(0);
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->string('commission_source')->nullable();
            $table->decimal('payment_fee_share', 14, 2)->default(0);
            $table->decimal('vendor_net', 14, 2)->default(0);

            $table->enum('settlement_status', [
                'pending', 'on_hold', 'available', 'in_payout', 'paid', 'reversed', 'cancelled',
            ])->default('pending');
            $table->timestamp('available_at')->nullable();

            $table->enum('fulfilment_status', [
                'unfulfilled', 'processing', 'shipped', 'delivered', 'completed', 'cancelled',
            ])->default('unfulfilled');
            $table->string('shipping_carrier')->nullable();
            $table->string('tracking_number')->nullable();

            $table->timestamps();

            $table->index(['vendor_profile_id', 'settlement_status']);
            $table->index('fulfilment_status');
        });

        Schema::create('marketplace_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('marketplace_suborder_id')->constrained('marketplace_suborders')->cascadeOnDelete();
            $table->foreignUuid('offer_id')->nullable()->constrained('marketplace_offers')->nullOnDelete();

            $table->string('type');
            $table->string('title');
            $table->decimal('unit_price', 14, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('line_total', 14, 2);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->json('custom_fields')->nullable();

            $table->timestamp('reservation_datetime')->nullable();
            $table->timestamp('reservation_end')->nullable();
            $table->enum('fulfilment_status', [
                'unfulfilled', 'processing', 'shipped', 'delivered', 'completed', 'cancelled',
            ])->default('unfulfilled');
            $table->unsignedInteger('download_count')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_order_items');
        Schema::dropIfExists('marketplace_suborders');
        Schema::dropIfExists('marketplace_orders');
    }
};
