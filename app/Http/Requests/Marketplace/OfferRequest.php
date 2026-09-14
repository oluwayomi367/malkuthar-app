<?php

namespace App\Http\Requests\Marketplace;

use App\Models\Marketplace\MarketplaceCategory;
use App\Services\Marketplace\CategoryFieldSchemaResolver;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * MARKETPLACE — création / édition d'une offre par un vendeur.
 *
 * Règles dynamiques : dépendent du type d'offre et du schéma de champs
 * personnalisés de la catégorie (propres + hérités). Gère aussi les visuels
 * (image principale + galerie) et, pour les produits physiques, les
 * variantes (couleur, taille…).
 */
class OfferRequest extends FormRequest
{
    private ?MarketplaceCategory $category = null;

    public function authorize(): bool
    {
        return $this->attributes->get('vendorProfile') !== null;
    }

    public function category(): ?MarketplaceCategory
    {
        if ($this->category === null && $this->filled('category_id')) {
            $this->category = MarketplaceCategory::find($this->input('category_id'));
        }

        return $this->category;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $offer = $this->route('offer');
        $type = $this->input('type', $offer?->type);

        $rules = [
            'category_id' => ['required', Rule::exists('marketplace_categories', 'id')->where('is_active', true)],
            'type' => [$offer ? 'nullable' : 'required', 'in:physical,digital,service,training'],
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:40', 'max:20000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:255'],

            // Visuels — communs à tous les types.
            'main_image' => array_merge(['nullable'], $this->imageFileRules()),
            'remove_main_image' => ['nullable', 'boolean'],
            'gallery' => ['nullable', 'array', 'max:12'],
            'gallery.*' => $this->imageFileRules(),
            'gallery_remove' => ['nullable', 'array'],
            'gallery_remove.*' => ['string'],
        ];

        $rules += match ($type) {
            'physical' => [
                'detail.sku' => ['nullable', 'string', 'max:64'],
                'detail.stock' => ['required_without:variants', 'nullable', 'integer', 'min:0'],
                'detail.weight_grams' => ['nullable', 'integer', 'min:0', 'max:1000000'],
                'detail.length_mm' => ['nullable', 'integer', 'min:0', 'max:100000'],
                'detail.width_mm' => ['nullable', 'integer', 'min:0', 'max:100000'],
                'detail.height_mm' => ['nullable', 'integer', 'min:0', 'max:100000'],
                'detail.handling_days' => ['nullable', 'integer', 'min:0', 'max:60'],
                'detail.origin_region' => ['nullable', 'string', 'max:120'],

                // Variantes (couleur, taille, modèle…).
                'variants' => ['nullable', 'array', 'max:100'],
                'variants.*.id' => ['nullable', 'string'],
                'variants.*.option_values' => ['required_with:variants', 'array', 'min:1'],
                'variants.*.option_values.*' => ['nullable', 'string', 'max:80'],
                'variants.*.sku' => ['nullable', 'string', 'max:64'],
                'variants.*.price_override' => ['nullable', 'numeric', 'min:0'],
                'variants.*.stock' => ['required_with:variants', 'integer', 'min:0'],
                'variants.*.is_active' => ['nullable', 'boolean'],
                'variants.*.image' => array_merge(['nullable'], $this->imageFileRules()),
            ],
            'digital' => [
                'detail.delivery_type' => ['required', 'in:file,external_link,license_keys'],
                'detail.external_url' => ['nullable', 'url', 'required_if:detail.delivery_type,external_link'],
                'detail.file_path' => ['nullable', 'string', 'max:2048'],
                'detail.download_limit' => ['nullable', 'integer', 'min:1', 'max:999'],
                'detail.version_label' => ['nullable', 'string', 'max:60'],
            ],
            'service' => [
                'detail.duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
                'detail.delivery_mode' => ['required', 'in:online,onsite,either'],
                'detail.location' => ['nullable', 'string', 'max:255'],
                'detail.capacity_per_slot' => ['required', 'integer', 'min:1', 'max:100'],
                'detail.min_notice_hours' => ['nullable', 'integer', 'min:0', 'max:720'],
            ],
            'training' => [
                'detail.format' => ['required', 'in:self_paced,cohort,live'],
                'detail.language' => ['required', 'string', 'max:40'],
                'detail.level' => ['nullable', 'in:beginner,intermediate,advanced'],
                'detail.seats' => ['nullable', 'integer', 'min:1', 'max:100000'],
                'detail.access_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            ],
            default => [],
        };

        if ($category = $this->category()) {
            $rules += app(CategoryFieldSchemaResolver::class)->validationRules($category);
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $category = $this->category();
            $offer = $this->route('offer');
            $type = $this->input('type', $offer?->type);

            if ($category && $type && ! $category->allowsType($type)) {
                $validator->errors()->add('type', "La catégorie « {$category->name} » n'accepte pas les offres de type « {$type} ».");
            }

            if ($category) {
                $min = $category->rule('min_price');
                $max = $category->rule('max_price');
                $price = (float) $this->input('price');
                if ($min !== null && $price < (float) $min) {
                    $validator->errors()->add('price', "Le prix minimum pour cette catégorie est {$min}.");
                }
                if ($max !== null && $price > (float) $max) {
                    $validator->errors()->add('price', "Le prix maximum pour cette catégorie est {$max}.");
                }
            }

            // SKU de variantes distincts entre eux.
            $skus = collect($this->input('variants', []))
                ->pluck('sku')->filter()->map(fn ($s) => strtolower(trim($s)));
            if ($skus->count() !== $skus->unique()->count()) {
                $validator->errors()->add('variants', 'Les SKU des variantes doivent être distincts.');
            }
        });
    }

    /**
     * Données normalisées pour OfferWriter (chemins d'images ajoutés ensuite
     * par le contrôleur via OfferMediaService).
     *
     * @return array<string, mixed>
     */
    public function forWriter(): array
    {
        $validated = $this->validated();
        $offer = $this->route('offer');
        $type = $validated['type'] ?? $offer?->type;

        $custom = [];
        if ($category = $this->category()) {
            foreach (app(CategoryFieldSchemaResolver::class)->schemaFor($category) as $field) {
                $value = data_get($validated, "custom_fields.{$field->key}");
                if ($value !== null) {
                    $custom[$field->key] = $value;
                }
            }
        }

        $data = [
            'type' => $type,
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'],
            'price' => (float) $validated['price'],
            'sale_price' => isset($validated['sale_price']) ? (float) $validated['sale_price'] : null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'custom_fields' => $custom,
            'detail' => $this->normalizeDetail($validated['detail'] ?? []),
        ];

        if ($type === 'physical') {
            $data['variants'] = collect($this->input('variants', []))->map(fn ($v, $i) => [
                'id' => $v['id'] ?? null,
                'option_values' => array_filter((array) ($v['option_values'] ?? []), fn ($x) => $x !== null && $x !== ''),
                'sku' => $v['sku'] ?? null,
                'price_override' => ($v['price_override'] ?? null) !== '' ? ($v['price_override'] ?? null) : null,
                'stock' => (int) ($v['stock'] ?? 0),
                'is_active' => filter_var($v['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'position' => $i,
            ])->values()->all();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    private function normalizeDetail(array $detail): array
    {
        foreach (['certificate', 'drip_enabled', 'has_variants'] as $bool) {
            if (array_key_exists($bool, $detail)) {
                $detail[$bool] = filter_var($detail[$bool], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $detail;
    }

    /**
     * Un <input type="file"> vide arrive comme UploadedFile invalide : on le
     * retire pour ne pas faire échouer la validation quand rien n'est envoyé.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        $data = parent::validationData();

        if (array_key_exists('main_image', $data) && ! $this->isRealUpload($data['main_image'])) {
            unset($data['main_image']);
        }

        if (array_key_exists('gallery', $data)) {
            $data['gallery'] = array_values(array_filter((array) $data['gallery'], fn ($f) => $this->isRealUpload($f)));
            if ($data['gallery'] === []) {
                unset($data['gallery']);
            }
        }

        foreach ((array) ($data['variants'] ?? []) as $i => $v) {
            if (isset($v['image']) && ! $this->isRealUpload($v['image'])) {
                unset($data['variants'][$i]['image']);
            }
        }

        return $data;
    }

    private function isRealUpload(mixed $file): bool
    {
        return $file instanceof UploadedFile && $file->isValid();
    }

    /** @return array<int, mixed> */
    private function imageFileRules(): array
    {
        return [
            'file',
            'max:5120',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! $value instanceof UploadedFile) {
                    return;
                }
                $ext = strtolower($value->getClientOriginalExtension());
                if (! in_array($ext, ['jpg', 'jpeg', 'jpe', 'jfif', 'png', 'webp', 'avif', 'gif'], true)) {
                    $fail('Le fichier doit être une image JPG, PNG, GIF, WebP ou AVIF.');

                    return;
                }
                if ($ext !== 'avif' && $value->isValid() && @getimagesize($value->getRealPath()) === false) {
                    $fail("« {$value->getClientOriginalName()} » n'est pas une image valide.");
                }
            },
        ];
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('Espace vendeur requis.');
    }

    protected function passedValidation(): void
    {
        if (($cat = $this->category()) && ! $cat->is_active) {
            throw ValidationException::withMessages(['category_id' => 'Catégorie indisponible.']);
        }
    }
}
