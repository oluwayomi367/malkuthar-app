<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table WITHDRAWALS — demandes de retrait de l'affilié (§50-§58).
 * Le débit effectif du wallet doit être matérialisé par une ligne dans
 * ledger_entries (direction=debit, source_type=withdrawal), jamais par une
 * simple décrémentation du solde.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('wallet_id')->constrained('wallets')->restrictOnDelete();

            $table->string('reference')->unique()->comment('Ex: WD-000001 (§58)');

            $table->decimal('amount', 14, 2);
            $table->decimal('fee', 14, 2)->default(0)->comment('§57 - frais fixes/pourcentage');
            $table->decimal('net_amount', 14, 2)->comment('amount - fee, effectivement envoyé au prestataire');
            $table->string('currency', 3)->default('XOF');

            // §52-§53 - moyens de retrait multi-pays / multi-prestataires
            $table->enum('method', ['mobile_money', 'bank_account', 'e_wallet', 'other']);
            $table->string('country')->nullable();
            $table->string('payout_provider')->nullable();
            $table->json('account_details')->comment('numéro mobile money, IBAN, etc.');

            // §41 - statuts financiers
            $table->enum('status', [
                'pending', 'processing', 'paid', 'failed', 'cancelled',
            ])->default('pending');

            $table->string('failure_reason')->nullable();
            $table->string('provider_reference')->nullable()->comment('référence transaction chez le prestataire de payout');

            // §55-§56 - sécurité renforcée (OTP, etc.)
            $table->boolean('security_verified')->default(false);
            $table->string('verification_method')->nullable();

            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });

        // Contrainte différée : ledger_entries.withdrawal_id -> withdrawals.id
        // (ajoutée ici car withdrawals n'existait pas encore lors de la
        // création de la table ledger_entries).
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->foreign('withdrawal_id')
                ->references('id')->on('withdrawals')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropForeign(['withdrawal_id']);
        });

        Schema::dropIfExists('withdrawals');
    }
};
