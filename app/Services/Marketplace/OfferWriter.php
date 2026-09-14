<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\VendorProfile;
use Illuminate\Support\Str;

/**
 * MARKETPLACE — écriture cohérente d'une offre : ligne cœur + table de détail
 * du type + champs personnalisés. Un seul chemin d'écriture, réutilisé pour
 * l'édition d'un brouillon ET pour l'application d'une révision approuvée.
 */
class OfferWriter
{
    /**
     * @param  array<string, mixed>  $data  déjà validé (OfferRequest::forModel())
     */
    public function create(VendorProfile $vendor, array $data): MarketplaceOffer
    {
        $offer = new MarketplaceOffer([
            'type' => $data['type'],
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'status' => 'draft',
            'currency' => $data['currency'] ?? $vendor->payout_currency ?? 'XOF',
        ]);
        $offer->vendor()->associate($vendor);
        $offer->category()->associate($data['category_id']);
        $offer->save();

        $this->applyData($offer, $data);

        return $offer->refresh();
    }

    /**
     * Applique un jeu de données à la ligne cœur + au détail de type.
     * N'affecte NI le statut, NI le versionnage, NI la mise en ligne.
     *
     * @param  array<string, mixed>  $data
     */
    public function applyData(MarketplaceOffer $offer, array $data): MarketplaceOffer
    {
        $offer->fill([
            'title' => $data['title'] ?? $offer->title,
            'summary' => $data['summary'] ?? null,
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? $offer->price,
            'sale_price' => $data['sale_price'] ?? null,
            'main_image_path' => $data['main_image_path'] ?? $offer->main_image_path,
            'gallery' => $data['gallery'] ?? $offer->gallery,
            'custom_fields' => $data['custom_fields'] ?? [],
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
        ]);

        if (! empty($data['category_id']) && $data['category_id'] !== $offer->category_id) {
            $offer->category()->associate($data['category_id']);
        }

        $offer->save();

        $this->applyTypeDetail($offer, $data['detail'] ?? []);

        if ($offer->type === 'physical' && array_key_exists('variants', $data)) {
            $this->syncVariants($offer, $data['variants'] ?? []);
        }

        return $offer;
    }

    /**
     * Recopie le contenu d'une révision (payload) sur l'offre — utilisé à
     * l'approbation d'une offre déjà en ligne.
     *
     * @param  array<string, mixed>  $payload
     */
    public function applyPayload(MarketplaceOffer $offer, array $payload): MarketplaceOffer
    {
        return $this->applyData($offer, $payload);
    }

    /**
     * Instantané complet de l'offre pour une révision.
     *
     * @return array<string, mixed>
     */
    public function snapshot(MarketplaceOffer $offer): array
    {
        $offer->loadMissing('physical', 'digital', 'service', 'training');

        return [
            'type' => $offer->type,
            'category_id' => $offer->category_id,
            'title' => $offer->title,
            'summary' => $offer->summary,
            'description' => $offer->description,
            'price' => (float) $offer->price,
            'sale_price' => $offer->sale_price !== null ? (float) $offer->sale_price : null,
            'currency' => $offer->currency,
            'main_image_path' => $offer->main_image_path,
            'gallery' => $offer->gallery,
            'custom_fields' => $offer->custom_fields ?? [],
            'seo_title' => $offer->seo_title,
            'seo_description' => $offer->seo_description,
            'detail' => $offer->detail()?->only($this->detailColumns($offer->type)) ?? [],
            'variants' => $offer->type === 'physical'
                ? $offer->variants()->get()->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'option_values' => $v->option_values,
                    'sku' => $v->sku,
                    'price_override' => $v->price_override !== null ? (float) $v->price_override : null,
                    'stock' => $v->stock,
                    'image_path' => $v->image_path,
                    'position' => $v->position,
                    'is_active' => $v->is_active,
                ])->all()
                : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function applyTypeDetail(MarketplaceOffer $offer, array $detail): void
    {
        $relation = $offer->type;             // physical | digital | service | training
        $model = $offer->{$relation}()->firstOrNew(['offer_id' => $offer->id]);
        $model->fill(array_intersect_key($detail, array_flip($this->detailColumns($offer->type))));
        $model->offer_id = $offer->id;
        $model->save();
    }

    /**
     * Upsert des variantes : met à jour celles avec un `id`, crée les autres,
     * soft-delete celles absentes de la liste. Le stock réservé et l'image
     * sont préservés lors d'une mise à jour.
     *
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function syncVariants(MarketplaceOffer $offer, array $variants): void
    {
        $keptIds = [];

        foreach (array_values($variants) as $position => $row) {
            $options = (array) ($row['option_values'] ?? []);
            $attributes = [
                'name' => $row['name'] ?? $this->variantName($options),
                'option_values' => $options,
                'sku' => $row['sku'] ?? null,
                'price_override' => ($row['price_override'] ?? null) !== null && $row['price_override'] !== ''
                    ? (float) $row['price_override'] : null,
                'stock' => (int) ($row['stock'] ?? 0),
                'position' => (int) ($row['position'] ?? $position),
                'is_active' => (bool) ($row['is_active'] ?? true),
            ];

            if (! empty($row['image_path'])) {
                $attributes['image_path'] = $row['image_path'];
            }

            if (! empty($row['id']) && ($existing = $offer->variants()->withTrashed()->find($row['id']))) {
                $existing->fill($attributes);
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->save();
                $keptIds[] = $existing->id;
            } else {
                $created = $offer->variants()->create($attributes + ['stock_reserved' => 0]);
                $keptIds[] = $created->id;
            }
        }

        $offer->variants()->whereNotIn('id', $keptIds)->get()
            ->each(fn ($v) => $v->stock_reserved > 0 ? $v->update(['is_active' => false]) : $v->delete());

        $offer->physical()->update(['has_variants' => $offer->variants()->where('is_active', true)->exists()]);
    }

    /** @param  array<string, mixed>  $options */
    private function variantName(array $options): string
    {
        return collect($options)->values()->filter()->implode(' / ') ?: 'Variante';
    }

    /** @return array<int, string> */
    private function detailColumns(string $type): array
    {
        return match ($type) {
            'physical' => ['sku', 'barcode', 'stock', 'stock_reserved', 'low_stock_threshold',
                'weight_grams', 'length_mm', 'width_mm', 'height_mm', 'shipping_class',
                'handling_days', 'has_variants', 'origin_region'],
            'digital' => ['delivery_type', 'file_path', 'file_size', 'file_original_name',
                'external_url', 'download_limit', 'link_ttl_days', 'version_label'],
            'service' => ['duration_minutes', 'delivery_mode', 'location', 'service_area',
                'buffer_minutes', 'min_notice_hours', 'max_advance_days', 'capacity_per_slot',
                'booking_confirmation', 'timezone'],
            'training' => ['format', 'level', 'language', 'total_duration_minutes', 'certificate',
                'cohort_starts_at', 'cohort_ends_at', 'seats', 'access_days', 'curriculum', 'drip_enabled'],
            default => [],
        };
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'offre';
        $slug = $base;
        $i = 2;

        while (MarketplaceOffer::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
