<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §16 — lien de visioconférence éventuel pour un service.
 * §19 — traçabilité de l'envoi du rappel de rendez-vous.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('meeting_link')->nullable()->after('delivery_mode');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('download_count');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('meeting_link');
        });
    }
};
