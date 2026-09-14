<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MARKETPLACE — module de règlement des vendeurs, indépendant du wallet
 * d'affiliation.
 *
 *  - `vendor_ledger_entries` : registre financier immuable du vendeur ;
 *  - `vendor_balances` : cache dérivé du registre (jamais la source) ;
 *  - `vendor_payouts` : demandes de versement ;
 *  - `marketplace_payout_accounts` : moyens de versement (cooldown au
 *    changement, patron §56).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_payout_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_profile_id')->constrained('vendor_profiles')->cascadeOnDelete();
            $table->enum('method', ['mobile_money', 'bank_transfer', 'manual']);
            $table->string('label');
            $table->text('details')->nullable(); // chiffré (cast encrypted:array)
            $table->boolean('is_default')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();

            $table->index('vendor_profile_id');
        });

        Schema::create('vendor_balances', function (Blueprint $table) {
            $table->foreignUuid('vendor_profile_id')->primary()
                ->constrained('vendor_profiles')->cascadeOnDelete();
            $table->char('currency', 3)->default('XOF');
            $table->decimal('pending_amount', 14, 2)->default(0);
            $table->decimal('available_amount', 14, 2)->default(0);
            $table->decimal('reserved_amount', 14, 2)->default(0);
            $table->decimal('lifetime_earned', 14, 2)->default(0);
            $table->decimal('lifetime_paid', 14, 2)->default(0);
            $table->decimal('lifetime_reversed', 14, 2)->default(0);
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('vendor_payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->foreignUuid('vendor_profile_id')->constrained('vendor_profiles')->restrictOnDelete();
            $table->foreignUuid('payout_account_id')->nullable()
                ->constrained('marketplace_payout_accounts')->nullOnDelete();

            $table->decimal('amount', 14, 2);
            $table->decimal('fee', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2);
            $table->char('currency', 3)->default('XOF');
            $table->enum('method', ['mobile_money', 'bank_transfer', 'manual'])->default('manual');
            $table->json('destination')->nullable();

            $table->enum('status', ['requested', 'approved', 'processing', 'paid', 'failed', 'cancelled'])
                ->default('requested');
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->foreignUuid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_profile_id', 'status']);
        });

        Schema::create('vendor_ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_profile_id')->constrained('vendor_profiles')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->enum('direction', ['credit', 'debit']);
            $table->enum('source_type', [
                'sale', 'refund_reversal', 'payout', 'payout_reversal', 'adjustment', 'chargeback',
            ]);
            $table->foreignUuid('marketplace_suborder_id')->nullable()
                ->constrained('marketplace_suborders')->nullOnDelete();
            $table->foreignUuid('vendor_payout_id')->nullable()
                ->constrained('vendor_payouts')->nullOnDelete();

            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('XOF');
            $table->enum('status', ['pending', 'available', 'processing', 'paid', 'reversed', 'cancelled'])
                ->default('pending');
            $table->timestamp('available_at')->nullable();
            $table->string('description')->nullable();
            $table->foreignUuid('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('adjustment_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['vendor_profile_id', 'status']);
            $table->index('source_type');
        });

        Schema::create('vendor_ledger_entry_status_changes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_ledger_entry_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('context')->nullable();
            $table->string('reason')->nullable();
            $table->uuid('admin_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Noms de contraintes explicites : les noms auto-générés dépassent
            // la limite MySQL de 64 caractères (nom de table déjà long).
            $table->foreign('vendor_ledger_entry_id', 'vlesc_entry_fk')
                ->references('id')->on('vendor_ledger_entries')->cascadeOnDelete();
            $table->foreign('admin_id', 'vlesc_admin_fk')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_ledger_entry_status_changes');
        Schema::dropIfExists('vendor_ledger_entries');
        Schema::dropIfExists('vendor_payouts');
        Schema::dropIfExists('vendor_balances');
        Schema::dropIfExists('marketplace_payout_accounts');
    }
};
