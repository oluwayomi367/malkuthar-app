<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables d'infrastructure d'authentification Laravel.
 *
 * NB : la table `users` métier (compte unique client/affilié, §30) est
 * créée par la migration 2024_01_01_000001_create_users_table.php avec son
 * propre schéma (first_name/last_name, arbre de parrainage, identifiant
 * affilié...). On ne garde ici que `password_reset_tokens` et `sessions`,
 * qui restent des tables standard Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // user_id reste un simple index (pas de contrainte FK) : la table
            // users utilise des UUID, on stocke donc l'id en string.
            $table->string('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
