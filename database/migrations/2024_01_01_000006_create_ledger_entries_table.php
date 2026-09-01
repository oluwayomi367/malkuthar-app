<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table LEDGER_ENTRIES — registre comptable immuable du wallet (§48-§49).
 *
 * Chaque commission, retrait ou correction admin génère UNE ligne. Le solde
 * du wallet est (re)calculable à tout instant en sommant les lignes
 * (credit - debit) filtrées par statut. Une ligne ne doit jamais être
 * modifiée après création : pour corriger, on crée une nouvelle ligne
 * d'ajustement inverse (§49).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            // Référence unique lisible, ex: COM-20260808-000001 (§47)
            $table->string('reference')->unique();

            // Sens de l'écriture
            $table->enum('direction', ['credit', 'debit']);

            // Origine de l'écriture
            $table->enum('source_type', [
                'commission',        // vente affiliée (§39)
                'commission_reversal', // remboursement -> reprise (§80)
                'withdrawal',        // retrait (§50)
                'withdrawal_failed_refund', // retrait échoué -> fonds recrédités (§10 Test 10)
                'adjustment',        // correction manuelle admin (§49)
            ]);

            // Identifiants de traçabilité (§67)
            $table->uuid('source_id')->nullable()->comment('id de la commission/retrait/ajustement lié');
            $table->foreignUuid('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignUuid('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            // Contrainte de clé étrangère ajoutée dans la migration withdrawals
            // (la table withdrawals n'existe pas encore à ce stade).
            $table->uuid('withdrawal_id')->nullable();

            // Niveau d'affiliation concerné (1, 2 ou 3) - null si non applicable (§29, §39)
            $table->unsignedTinyInteger('level')->nullable();

            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('XOF');

            // §40-§41 - Statuts financiers
            $table->enum('status', [
                'pending', 'available', 'processing', 'paid', 'cancelled', 'reversed', 'failed',
            ])->default('pending');

            $table->text('description')->nullable();

            // Ajustement manuel (§49) : traçabilité de l'administrateur responsable
            $table->foreignUuid('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('adjustment_reason')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamp('available_at')->nullable()->comment('§42 - date à laquelle la commission devient retirable');

            $table->timestamps();

            $table->index(['wallet_id', 'status']);
            $table->index(['user_id', 'source_type']);
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
