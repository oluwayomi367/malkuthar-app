<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_offers', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 4)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_offers', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
    }
};
