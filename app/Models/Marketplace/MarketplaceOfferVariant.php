<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * MARKETPLACE — variante d'une offre physique.
 */
class MarketplaceOfferVariant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'offer_id', 'name', 'option_values', 'sku', 'price_override',
        'stock', 'stock_reserved', 'image_path', 'position', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'option_values' => 'array',
            'price_override' => 'decimal:2',
            'stock' => 'integer',
            'stock_reserved' => 'integer',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOffer::class, 'offer_id');
    }

    public function effectivePrice(?MarketplaceOffer $offer = null): float
    {
        if ($this->price_override !== null) {
            return (float) $this->price_override;
        }

        $offer ??= $this->relationLoaded('offer') ? $this->offer : $this->offer()->first();

        return (float) ($offer?->effectivePrice() ?? 0);
    }

    public function availableStock(): int
    {
        return max(0, $this->stock - $this->stock_reserved);
    }

    /** « Couleur : Rouge · Taille : M ». */
    public function optionsLabel(): string
    {
        return collect($this->option_values ?? [])
            ->map(fn ($v, $k) => Str::ucfirst((string) $k).' : '.$v)
            ->implode(' · ');
    }
}
