<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MARKETPLACE — workflow de validation des offres.
 *
 * `marketplace_offer_revisions` : instantané du contenu soumis à chaque
 * soumission. La version publiée reste en ligne pendant la revue ; à
 * l'approbation, le `payload` est recopié sur la ligne cœur.
 *
 * `marketplace_offer_reviews` : journal immuable (append-only) des décisions
 * de modération.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_offer_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('offer_id')->constrained('marketplace_offers')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('payload');
            $table->foreignUuid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at');
            $table->enum('state', ['pending', 'approved', 'rejected', 'superseded'])->default('pending');
            $table->timestamps();

            $table->unique(['offer_id', 'version']);
            $table->index(['offer_id', 'state']);
        });

        Schema::create('marketplace_offer_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('offer_id')->constrained('marketplace_offers')->cascadeOnDelete();
            $table->unsignedInteger('offer_version');
            $table->foreignUuid('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->enum('decision', ['approved', 'rejected', 'changes_requested']);
            $table->text('note')->nullable();
            $table->json('checklist')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['offer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_offer_reviews');
        Schema::dropIfExists('marketplace_offer_revisions');
    }
};
