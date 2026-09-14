<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table AUDIT_LOGS — journal d'audit (§68) : qui, quoi, quand, depuis
 * quelle IP, quelle modification. Immuable : aucune UPDATE/DELETE
 * applicative, seulement des INSERT (pas de `updated_at`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // ex: affiliate.suspended, wallet.adjusted

            $table->string('subject_type')->nullable(); // ex: App\Models\User
            $table->uuid('subject_id')->nullable();

            $table->string('description')->nullable();
            $table->json('changes')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index('admin_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
