<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §55-§56 — sécurité renforcée autour du moyen de retrait :
 *  - wallets.withdrawal_method_changed_at sert au cooldown anti-fraude
 *    après une modification du compte de retrait ;
 *  - otp_codes stocke des codes à usage unique hachés (jamais en clair).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->timestamp('withdrawal_method_changed_at')->nullable()->after('default_withdrawal_account');
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('purpose'); // ex: withdrawal_method_change
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'purpose', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('withdrawal_method_changed_at');
        });
    }
};
