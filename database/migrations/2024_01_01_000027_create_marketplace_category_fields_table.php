<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MARKETPLACE — champs personnalisés par catégorie.
 *
 * Définit les attributs saisis par le vendeur pour une offre de cette
 * catégorie. Une offre hérite des champs de sa catégorie ET de tous ses
 * ancêtres (résolution par App\Services\Marketplace\CategoryFieldSchemaResolver).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_category_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained('marketplace_categories')->cascadeOnDelete();

            // Nom machine (snake). Sert de clé dans offers.custom_fields.
            $table->string('key');
            $table->string('label');
            $table->string('help_text')->nullable();

            $table->enum('type', [
                'text', 'textarea', 'number', 'boolean',
                'select', 'multiselect', 'date', 'url', 'file',
            ]);

            $table->json('options')->nullable();     // select / multiselect
            $table->json('validation')->nullable();  // min, max, step, regex, mimes

            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_variant_axis')->default(false);

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->unique(['category_id', 'key']);
            $table->index(['category_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_category_fields');
    }
};
