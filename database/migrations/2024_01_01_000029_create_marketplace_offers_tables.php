<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MARKETPLACE — offres des vendeurs.
 *
 * Table cœur `marketplace_offers` + une table de détail 1–1 par type
 * (physique / digital / service / formation). Seule la table du type de
 * l'offre est renseignée. Les valeurs des champs personnalisés vivent en
 * JSON sur la table cœur, validées à l'écriture contre le schéma de la
 * catégorie (CategoryFieldSchemaResolver).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_profile_id')->constrained('vendor_profiles')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('marketplace_categories')->restrictOnDelete();

            $table->enum('type', ['physical', 'digital', 'service', 'training']);
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary', 255)->nullable();
            $table->longText('description')->nullable();

            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('sale_price', 14, 2)->nullable();
            $table->char('currency', 3)->default('XOF');

            $table->string('main_image_path')->nullable();
            $table->json('gallery')->nullable();
            $table->json('custom_fields')->nullable();

            $table->enum('status', [
                'draft', 'pending_review', 'changes_requested',
                'approved', 'rejected', 'suspended', 'archived',
            ])->default('draft');
            $table->boolean('is_online')->default(false);
            $table->unsignedInteger('version')->default(0);

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_published_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('review_note')->nullable();

            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('sales_count')->default(0);

            $table->string('seo_title')->nullable();
            $table->string('seo_description', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_online']);
            $table->index(['vendor_profile_id', 'status']);
            $table->index('category_id');
            $table->index('type');
        });

        Schema::create('marketplace_offer_physical', function (Blueprint $table) {
            $table->foreignUuid('offer_id')->primary()->constrained('marketplace_offers')->cascadeOnDelete();
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable();
            $table->integer('stock')->default(0);
            $table->integer('stock_reserved')->default(0);
            $table->integer('low_stock_threshold')->nullable();
            $table->integer('weight_grams')->nullable();
            $table->integer('length_mm')->nullable();
            $table->integer('width_mm')->nullable();
            $table->integer('height_mm')->nullable();
            $table->string('shipping_class')->nullable();
            $table->unsignedTinyInteger('handling_days')->nullable();
            $table->boolean('has_variants')->default(false);
            $table->string('origin_region')->nullable();
        });

        Schema::create('marketplace_offer_digital', function (Blueprint $table) {
            $table->foreignUuid('offer_id')->primary()->constrained('marketplace_offers')->cascadeOnDelete();
            $table->enum('delivery_type', ['file', 'external_link', 'license_keys'])->default('file');
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_original_name')->nullable();
            $table->string('external_url')->nullable();
            $table->unsignedInteger('download_limit')->nullable();
            $table->unsignedInteger('link_ttl_days')->nullable();
            $table->string('version_label')->nullable();
        });

        Schema::create('marketplace_offer_service', function (Blueprint $table) {
            $table->foreignUuid('offer_id')->primary()->constrained('marketplace_offers')->cascadeOnDelete();
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->enum('delivery_mode', ['online', 'onsite', 'either'])->default('online');
            $table->string('location')->nullable();
            $table->string('service_area')->nullable();
            $table->unsignedInteger('buffer_minutes')->default(0);
            $table->unsignedInteger('min_notice_hours')->default(24);
            $table->unsignedInteger('max_advance_days')->default(60);
            $table->unsignedInteger('capacity_per_slot')->default(1);
            $table->enum('booking_confirmation', ['auto', 'manual'])->default('auto');
            $table->string('timezone')->default('Africa/Porto-Novo');
        });

        Schema::create('marketplace_offer_training', function (Blueprint $table) {
            $table->foreignUuid('offer_id')->primary()->constrained('marketplace_offers')->cascadeOnDelete();
            $table->enum('format', ['self_paced', 'cohort', 'live'])->default('self_paced');
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->nullable();
            $table->string('language')->default('fr');
            $table->unsignedInteger('total_duration_minutes')->nullable();
            $table->boolean('certificate')->default(false);
            $table->timestamp('cohort_starts_at')->nullable();
            $table->timestamp('cohort_ends_at')->nullable();
            $table->unsignedInteger('seats')->nullable();
            $table->unsignedInteger('seats_taken')->default(0);
            $table->unsignedInteger('access_days')->nullable();
            $table->json('curriculum')->nullable();
            $table->boolean('drip_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_offer_training');
        Schema::dropIfExists('marketplace_offer_service');
        Schema::dropIfExists('marketplace_offer_digital');
        Schema::dropIfExists('marketplace_offer_physical');
        Schema::dropIfExists('marketplace_offers');
    }
};
