<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * §18 — créneau hebdomadaire récurrent disponible pour un service.
 */
class ServiceSchedule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['product_id', 'weekday', 'start_time', 'end_time', 'capacity'];

    protected function casts(): array
    {
        return ['weekday' => 'integer', 'capacity' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
