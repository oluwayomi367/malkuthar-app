<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MARKETPLACE — profil praticien / vendeur.
 *
 * Un profil par utilisateur habilité à vendre. Le compte reste unique
 * (un `user` peut être client + affilié + vendeur) : c'est ce profil qui
 * porte l'habilitation marketplace, pas la table `users`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();

            $table->string('display_name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->text('bio')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();

            $table->string('region')->nullable();
            $table->char('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('phone')->nullable();

            // Validation du compte vendeur lui-même.
            $table->enum('status', ['pending', 'active', 'suspended', 'closed'])->default('pending');
            $table->boolean('is_trusted')->default(false);

            // Taux de commission spécifique au vendeur (fraction). Prioritaire
            // sur la catégorie et sur le défaut plateforme.
            $table->decimal('commission_rate_override', 6, 4)->nullable();

            $table->char('payout_currency', 3)->default('XOF');

            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('region');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_profiles');
    }
};
