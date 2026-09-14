<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceCategoryField;
use Illuminate\Support\Collection;

/**
 * MARKETPLACE — résout le schéma de champs personnalisés effectif d'une
 * catégorie : ses propres champs PLUS ceux de tous ses ancêtres.
 *
 * En cas de collision de `key`, le champ le plus proche de la feuille
 * (donc le plus spécifique) l'emporte.
 */
class CategoryFieldSchemaResolver
{
    /**
     * @return Collection<int, MarketplaceCategoryField>
     */
    public function schemaFor(MarketplaceCategory $category): Collection
    {
        // lineageIds() est déjà ordonné racine → feuille (dérivé de `path`).
        $lineageIds = $category->lineageIds();

        $byCategory = MarketplaceCategoryField::query()
            ->whereIn('category_id', $lineageIds)
            ->orderBy('position')
            ->get()
            ->groupBy('category_id');

        $merged = [];

        foreach ($lineageIds as $categoryId) {
            foreach ($byCategory->get($categoryId, collect()) as $field) {
                // Un champ de même `key` défini plus près de la feuille écrase
                // celui d'un ancêtre.
                $merged[$field->key] = $field;
            }
        }

        return collect(array_values($merged));
    }

    /**
     * Règles de validation Laravel indexées par `custom_fields.{key}`.
     *
     * @return array<string, array<int, string>>
     */
    public function validationRules(MarketplaceCategory $category): array
    {
        $rules = [];

        foreach ($this->schemaFor($category) as $field) {
            $rules["custom_fields.{$field->key}"] = $field->validationRules();

            if ($field->type === 'multiselect' && $field->options) {
                $rules["custom_fields.{$field->key}.*"] = ['in:'.implode(',', $field->optionValues())];
            }
        }

        return $rules;
    }

    /**
     * Clés des champs exposés comme facettes de recherche storefront.
     *
     * @return array<int, string>
     */
    public function filterableKeys(MarketplaceCategory $category): array
    {
        return $this->schemaFor($category)
            ->filter->is_filterable
            ->pluck('key')
            ->all();
    }
}
