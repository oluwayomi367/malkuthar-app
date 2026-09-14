<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * §12 — variante d'un produit physique. Chaque variante a son propre SKU,
 * son stock (disponible / réservé / vendu), un prix optionnel qui écrase
 * celui du produit, et une image.
 */
class ProductVariant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'product_id',
        'name',
        'options',
        'sku',
        'price',
        'stock_available',
        'stock_reserved',
        'stock_sold',
        'image',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'price' => 'decimal:2',
            'stock_available' => 'integer',
            'stock_reserved' => 'integer',
            'stock_sold' => 'integer',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Prix effectif : celui de la variante s'il est défini, sinon celui du
     * produit (promo comprise).
     */
    public function effectivePrice(): float
    {
        if ($this->price !== null) {
            return (float) $this->price;
        }

        $product = $this->relationLoaded('product') ? $this->product : $this->product()->first();

        return (float) ($product?->promotional_price ?? $product?->price ?? 0);
    }

    /**
     * Libellé « Couleur : Rouge · Taille : M » à partir des options.
     */
    public function optionsLabel(): string
    {
        return collect($this->options ?? [])
            ->map(fn ($v, $k) => ucfirst((string) $k).' : '.$v)
            ->implode(' · ');
    }
}
