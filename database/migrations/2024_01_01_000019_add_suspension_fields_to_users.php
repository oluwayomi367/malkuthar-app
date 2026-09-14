<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §63 — suspension d'un affilié : on conserve le motif catégorisé, le
 * détail et la date. Aucune donnée financière n'est supprimée : le
 * traitement des commissions / retraits passe par des transitions de
 * statut et des écritures de ledger, jamais par une suppression.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('status');
            $table->string('suspension_category')->nullable()->after('suspended_at');
            $table->text('suspension_reason')->nullable()->after('suspension_category');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['suspended_at', 'suspension_category', 'suspension_reason']);
        });
    }
};
