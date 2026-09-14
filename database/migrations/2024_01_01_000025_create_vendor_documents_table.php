<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MARKETPLACE — justificatifs (KYC) déposés par le vendeur à l'inscription,
 * revus un par un par un administrateur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_profile_id')->constrained('vendor_profiles')->cascadeOnDelete();

            $table->enum('type', ['id_card', 'business_registration', 'tax_id', 'bank_details', 'other']);
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note')->nullable();

            $table->timestamps();

            $table->index(['vendor_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
    }
};
