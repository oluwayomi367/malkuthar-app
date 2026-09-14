<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compteur atomique de réservations pour un (produit, créneau) donné.
 * Verrouillé en écriture par ServiceAvailabilityService pour empêcher un
 * dépassement de capacité (§18) en cas de réservations concurrentes.
 */
class ServiceSlotLock extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['product_id', 'slot_datetime', 'booked_count'];

    protected function casts(): array
    {
        return ['slot_datetime' => 'datetime', 'booked_count' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
