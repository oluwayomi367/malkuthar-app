<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * MARKETPLACE — création / édition d'une catégorie du catalogue.
 */
class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name', '')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $categoryId = $this->route('category')?->getKey();
        $parentId = $this->input('parent_id') ?: null;

        return [
            'parent_id' => [
                'nullable',
                Rule::exists('marketplace_categories', 'id'),
                // Pas de rattachement à soi-même.
                Rule::notIn(array_filter([$categoryId])),
            ],
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required', 'string', 'max:140',
                Rule::unique('marketplace_categories', 'slug')
                    ->where(fn ($q) => $q->where('parent_id', $parentId))
                    ->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:120'],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],

            'applicable_offer_types' => ['required', 'array', 'min:1'],
            'applicable_offer_types.*' => ['in:physical,digital,service,training'],

            'commission_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:90'],

            'ruleset' => ['nullable', 'array'],
            'ruleset.require_approval' => ['nullable', 'boolean'],
            'ruleset.requires_shipping' => ['nullable', 'boolean'],
            'ruleset.allow_variants' => ['nullable', 'boolean'],
            'ruleset.min_price' => ['nullable', 'numeric', 'min:0'],
            'ruleset.max_price' => ['nullable', 'numeric', 'min:0'],
            'ruleset.max_images' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    /**
     * Données prêtes pour le modèle (conversion % → fraction, ruleset nettoyé).
     *
     * @return array<string, mixed>
     */
    public function forModel(): array
    {
        $data = $this->safe()->except(['commission_rate_percent', 'ruleset']);

        $pct = $this->input('commission_rate_percent');
        $data['commission_rate'] = ($pct === null || $pct === '') ? null : round((float) $pct / 100, 4);

        $ruleset = array_filter([
            'require_approval' => $this->boolean('ruleset.require_approval'),
            'requires_shipping' => $this->boolean('ruleset.requires_shipping'),
            'allow_variants' => $this->boolean('ruleset.allow_variants'),
            'min_price' => $this->input('ruleset.min_price') !== null && $this->input('ruleset.min_price') !== ''
                ? (float) $this->input('ruleset.min_price') : null,
            'max_price' => $this->input('ruleset.max_price') !== null && $this->input('ruleset.max_price') !== ''
                ? (float) $this->input('ruleset.max_price') : null,
            'max_images' => $this->input('ruleset.max_images') ? (int) $this->input('ruleset.max_images') : null,
        ], fn ($v) => $v !== null && $v !== false);

        // require_approval doit rester explicite même à false.
        $ruleset['require_approval'] = $this->boolean('ruleset.require_approval');

        $data['ruleset'] = $ruleset;
        $data['parent_id'] = ($data['parent_id'] ?? null) ?: null;
        $data['position'] = $data['position'] ?? 0;
        $data['description'] = $data['description'] ?? null;

        return $data;
    }
}
