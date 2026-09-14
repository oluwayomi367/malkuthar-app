<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table SEQUENCES — compteurs atomiques nommés.
 *
 * Sert notamment à générer l'identifiant affilié unique et permanent
 * (§32 : MAL-AFF-000001, MAL-AFF-000002, ...). L'incrément se fait sous
 * verrou (lockForUpdate) pour rester correct en concurrence, y compris
 * sur MySQL en production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }
};
