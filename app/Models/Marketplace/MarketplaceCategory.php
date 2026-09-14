<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MARKETPLACE — catégorie de l'arbre du catalogue.
 *
 * `path` est un chemin matérialisé « /{ancestorId}/{ancestorId}/{selfId} »
 * maintenu automatiquement à l'enregistrement ; il permet de charger un
 * sous-arbre entier en une requête `where path like`.
 */
class MarketplaceCategory extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'icon',
        'image_path',
        'position',
        'is_active',
        'applicable_offer_types',
        'commission_rate',
        'ruleset',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'applicable_offer_types' => 'array',
            'commission_rate' => 'decimal:4',
            'ruleset' => 'array',
            'position' => 'integer',
            'depth' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (MarketplaceCategory $category) {
            // HasUuids ne pose la clé qu'au `creating` (après ce `saving`) :
            // on la génère ici pour pouvoir bâtir `path` dès la création.
            if (! $category->getKey()) {
                $category->{$category->getKeyName()} = $category->newUniqueId();
            }

            $parent = $category->parent_id
                ? static::withTrashed()->find($category->parent_id)
                : null;

            $category->depth = $parent ? $parent->depth + 1 : 0;
            $category->path = ($parent ? $parent->path : '').'/'.$category->getKey();
        });

        // Reconstruit le chemin des descendants quand une catégorie est
        // rattachée à un nouveau parent.
        static::updated(function (MarketplaceCategory $category) {
            if ($category->wasChanged('path')) {
                foreach ($category->children()->get() as $child) {
                    $child->save();
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('name');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(MarketplaceCategoryField::class, 'category_id')->orderBy('position');
    }

    /*
    |--------------------------------------------------------------------------
    | Arbre
    |--------------------------------------------------------------------------
    */

    /** IDs des ancêtres (racine → parent direct), soi-même exclu. */
    public function ancestorIds(): array
    {
        $segments = array_values(array_filter(explode('/', trim((string) $this->path, '/'))));

        return array_values(array_diff($segments, [(string) $this->getKey()]));
    }

    /** IDs de la lignée complète : ancêtres + soi-même. */
    public function lineageIds(): array
    {
        return [...$this->ancestorIds(), (string) $this->getKey()];
    }

    /** Ancêtres (racine → parent), triés par profondeur. */
    public function ancestors(): Collection
    {
        $ids = $this->ancestorIds();

        if ($ids === []) {
            return new Collection;
        }

        return static::query()->whereIn('id', $ids)->orderBy('depth')->get();
    }

    /** Scope : la catégorie et tout son sous-arbre. */
    public function scopeInSubtree(Builder $query, MarketplaceCategory $root): Builder
    {
        return $query->where(function (Builder $q) use ($root) {
            $q->where('id', $root->getKey())
                ->orWhere('path', 'like', $root->path.'/%');
        });
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** La catégorie admet-elle ce type d'offre ? */
    public function allowsType(string $type): bool
    {
        return in_array($type, $this->applicable_offer_types ?? [], true);
    }

    public function rule(string $key, mixed $default = null): mixed
    {
        return data_get($this->ruleset, $key, $default);
    }
}
