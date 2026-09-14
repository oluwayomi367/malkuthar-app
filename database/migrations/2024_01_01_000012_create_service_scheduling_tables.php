<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Calendrier des services (§18) :
 *  - service_schedules   : créneaux hebdomadaires récurrents (jours
 *                          disponibles, horaires, nombre de rendez-vous
 *                          possibles en parallèle sur un même créneau).
 *  - service_time_off    : exceptions (pauses, jours indisponibles,
 *                          congés) qui viennent RETRANCHER des schedules.
 *  - service_slot_locks  : compteur atomique de réservations par
 *                          (produit, date+heure), verrouillé en écriture
 *                          pour empêcher un dépassement de capacité en
 *                          cas de réservations concurrentes (même
 *                          principe que `sequences` pour l'ID affilié).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();

            $table->unsignedTinyInteger('weekday'); // 0 = dimanche ... 6 = samedi
            $table->time('start_time');
            $table->time('end_time');

            // §18 - nombre de rendez-vous possibles en parallèle sur un
            // même créneau (ex: 1 pour une consultation individuelle).
            $table->unsignedInteger('capacity')->default(1);

            $table->timestamps();

            $table->index(['product_id', 'weekday']);
        });

        Schema::create('service_time_off', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // NULL = s'applique à tous les services (ex: jour férié MALKUTHAR).
            $table->foreignUuid('product_id')->nullable()->constrained('products')->cascadeOnDelete();

            $table->date('date');
            // NULL+NULL = journée entière bloquée ; sinon plage horaire (pause).
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('reason')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'date']);
        });

        Schema::create('service_slot_locks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->dateTime('slot_datetime');
            $table->unsignedInteger('booked_count')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'slot_datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_slot_locks');
        Schema::dropIfExists('service_time_off');
        Schema::dropIfExists('service_schedules');
    }
};
