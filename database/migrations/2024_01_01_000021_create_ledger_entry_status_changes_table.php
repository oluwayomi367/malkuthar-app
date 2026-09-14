<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §41 — « Chaque changement de statut doit être enregistré. » Journal
 * append-only des transitions de statut d'une écriture du registre
 * (pending → available → processing → paid, ou cancelled / reversed /
 * failed). Immuable : une seule colonne temporelle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entry_status_changes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ledger_entry_id')->constrained('ledger_entries')->cascadeOnDelete();

            $table->string('from_status')->nullable(); // null = création
            $table->string('to_status');
            $table->string('context')->nullable();     // created / commission_release / refund / suspension / withdrawal_paid…
            $table->string('reason')->nullable();
            $table->foreignUuid('admin_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['ledger_entry_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entry_status_changes');
    }
};
