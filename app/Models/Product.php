<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * §4 - RÈGLE ABSOLUE : seul un produit type=digital peut avoir
 * is_affiliable=true. À faire respecter en Form Request / Service
 * (ex: rejeter is_affiliable=true si type != digital).
 */
class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'is_affiliable',
        'price',
        'promotional_price',
        'currency',
        'category',
        'sub_category',
        'main_image',
        'gallery',
        'status',
        'is_available',
        'file_path',
        'presentation_video',
        'sku',
        'stock_available',
        'stock_reserved',
        'weight',
        'dimensions',
        'duration_minutes',
        'location',
        'delivery_mode',
    ];

    protected function casts(): array
    {
        return [
            'is_affiliable' => 'boolean',
            'is_available' => 'boolean',
            'price' => 'decimal:2',
            'promotional_price' => 'decimal:2',
            'gallery' => 'array',
            'dimensions' => 'array',
            'weight' => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDigital($query)
    {
        return $query->where('type', 'digital');
    }

    public function scopePhysical($query)
    {
        return $query->where('type', 'physical');
    }

    public function scopeService($query)
    {
        return $query->where('type', 'service');
    }

    public function scopeAffiliable($query)
    {
        return $query->where('type', 'digital')->where('is_affiliable', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers métier
    |--------------------------------------------------------------------------
    */

    /**
     * §4 - Un produit n'est éligible à la commission que s'il est digital
     * ET explicitement marqué affiliable.
     */
    public function isEligibleForCommission(): bool
    {
        return $this->type === 'digital' && $this->is_affiliable === true;
    }
}
