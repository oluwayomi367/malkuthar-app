<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table USERS — compte unique client/affilié (cf. §30 "COMPTE MALKUTHAR").
 * - parent_id      : parrain direct (arbre de parrainage, cf. §35-§37)
 * - affiliate_id   : identifiant unique permanent (ex: MAL-AFF-000001, cf. §32)
 * - code_parrainage: code unique de parrainage (ex: JEAN125, cf. §34)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Identité
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('currency', 3)->default('XOF');

            // Rôle
            $table->enum('role', ['client', 'admin'])->default('client');

            // --- Espace affilié (§30-§37) ---
            $table->string('affiliate_id')->nullable()->unique()->comment('Ex: MAL-AFF-000001');
            $table->string('code_parrainage')->nullable()->unique()->comment('Ex: JEAN125');

            // Parrain direct (auto-référence). Une fois rattaché, ne doit plus
            // être modifiable par l'affilié lui-même (§35 - règle métier appliquée
            // au niveau du Service, pas de la migration).
            $table->uuid('parent_id')->nullable();

            $table->timestamp('affiliate_activated_at')->nullable();

            // Statut du compte (§62 - suspension d'un affilié)
            $table->enum('status', ['active', 'suspended', 'banned'])->default('active');

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes(); // les données financières/personnelles ne doivent pas être supprimées physiquement (§62)

            $table->foreign('parent_id')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->index('affiliate_id');
            $table->index('code_parrainage');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });
        Schema::dropIfExists('users');
    }
};
