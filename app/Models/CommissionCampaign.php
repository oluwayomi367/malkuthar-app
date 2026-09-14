<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * §27 — jeu de taux de commission (niveaux 1/2/3), éventuellement borné
 * dans le temps. Résolu par CommissionRateResolver.
 */
class CommissionCampaign extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'rate_l1',
        'rate_l2',
        'rate_l3',
        'starts_at',
        'ends_at',
        'is_active',
        'is_default',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'rate_l1' => 'decimal:4',
            'rate_l2' => 'decimal:4',
            'rate_l3' => 'decimal:4',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'integer',
        ];
    }

    /**
     * @return array<int, float> taux par niveau (fractions).
     */
    public function rates(): array
    {
        return [
            1 => (float) $this->rate_l1,
            2 => (float) $this->rate_l2,
            3 => (float) $this->rate_l3,
        ];
    }

    /** Campagnes actives couvrant la date donnée. */
    public function scopeCoveringDate(Builder $query, Carbon $at): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $at));
    }
}
