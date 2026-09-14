<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * §18 — pause / jour indisponible / congé. `product_id` null = s'applique
 * à tous les services (fermeture générale MALKUTHAR).
 */
class ServiceTimeOff extends Model
{
    use HasFactory, HasUuids;

    // Nom explicite : la pluralisation automatique de "TimeOff" donnerait
    // "service_time_offs", incohérent avec la migration.
    protected $table = 'service_time_off';

    protected $fillable = ['product_id', 'date', 'start_time', 'end_time', 'reason'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * true si toute la journée est bloquée (pas seulement une pause).
     */
    public function isFullDay(): bool
    {
        return $this->start_time === null && $this->end_time === null;
    }
}
