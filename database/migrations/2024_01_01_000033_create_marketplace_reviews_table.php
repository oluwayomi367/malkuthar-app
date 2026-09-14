<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MARKETPLACE — avis des acheteurs sur une offre (preuve d'achat requise :
 * un avis par ligne de commande).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('offer_id')->constrained('marketplace_offers')->cascadeOnDelete();
            $table->foreignUuid('vendor_profile_id')->constrained('vendor_profiles')->cascadeOnDelete();
            $table->foreignUuid('marketplace_order_item_id')->unique()
                ->constrained('marketplace_order_items')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->enum('status', ['pending', 'published', 'hidden'])->default('published');
            $table->text('vendor_reply')->nullable();
            $table->timestamp('vendor_replied_at')->nullable();

            $table->timestamps();

            $table->index(['offer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_reviews');
    }
};
