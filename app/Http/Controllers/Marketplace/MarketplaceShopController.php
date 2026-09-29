<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\MarketplaceOfferVariant;
use App\Models\Marketplace\MarketplaceOrderItem;
use App\Models\Marketplace\MarketplaceReview;
use App\Models\Marketplace\VendorProfile;
use App\Services\Marketplace\CategoryFieldSchemaResolver;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MARKETPLACE — boutique publique de la place de marché. Rendu via
 * Inertia/React (resources/js/Pages/Marketplace/*) — les URLs de fichiers
 * (Storage::url) sont résolues ICI, jamais côté client.
 */
class MarketplaceShopController extends Controller
{
    public function __construct(private readonly CategoryFieldSchemaResolver $schema) {}

    public function index(Request $request): Response
    {
        $pole = $request->query('pole');
        $poleMap = [
            'fashion_beauty' => 'mode-beaute-cosmetiques',
            'mode-beaute' => 'mode-beaute-cosmetiques',
            'mode' => 'mode-beaute-cosmetiques',
            'real_estate_hospitality' => 'residences-guest-houses-immobilier',
            'immobilier-sejours' => 'residences-guest-houses-immobilier',
            'immobilier' => 'residences-guest-houses-immobilier',
            'services_wellness' => 'services-praticiens-specialises',
            'services-praticiens' => 'services-praticiens-specialises',
            'services' => 'services-praticiens-specialises',
            'digital_academy' => 'savoirs-formations-en-ligne',
            'savoirs-formations' => 'savoirs-formations-en-ligne',
            'academy' => 'savoirs-formations-en-ligne',
        ];

        $activePoleSlug = $pole ? ($poleMap[$pole] ?? $pole) : null;
        $activePoleCategory = $activePoleSlug
            ? MarketplaceCategory::active()->roots()->where('slug', $activePoleSlug)->first()
            : null;

        $category = $request->filled('categorie')
            ? MarketplaceCategory::active()->where('slug', $request->query('categorie'))->first()
            : null;

        $type = in_array($request->query('type'), MarketplaceOffer::TYPES, true) ? $request->query('type') : null;
        $facets = collect((array) $request->query('f', []))->filter(fn ($v) => $v !== '' && $v !== null);

        $offers = MarketplaceOffer::query()
            ->publiclyVisible()
            ->with(['vendor:id,display_name,slug,region', 'category:id,name,slug', 'variants:id,offer_id,option_values,is_active'])
            ->when($category, fn (Builder $q) => $q->whereIn(
                'category_id',
                MarketplaceCategory::query()->inSubtree($category)->pluck('id'),
            ))
            ->when(! $category && $activePoleCategory, fn (Builder $q) => $q->whereIn(
                'category_id',
                MarketplaceCategory::query()->inSubtree($activePoleCategory)->pluck('id'),
            ))
            ->when($type, fn (Builder $q) => $q->where('type', $type))
            ->when($request->filled('region'), fn (Builder $q) => $q->whereHas(
                'vendor', fn (Builder $v) => $v->where('region', 'like', '%'.$request->query('region').'%'),
            ))
            ->when($request->filled('q'), function (Builder $q) use ($request) {
                $term = $request->query('q');
                $q->where(fn (Builder $sub) => $sub->where('title', 'like', "%{$term}%")
                    ->orWhere('summary', 'like', "%{$term}%"));
            })
            ->when($request->filled('prix_min'), fn (Builder $q) => $q->whereRaw(
                'coalesce(sale_price, price) >= cast(? as decimal)', [(float) $request->query('prix_min')],
            ))
            ->when($request->filled('prix_max'), fn (Builder $q) => $q->whereRaw(
                'coalesce(sale_price, price) <= cast(? as decimal)', [(float) $request->query('prix_max')],
            ))
            ->when($request->filled('vendeur'), fn (Builder $q) => $q->whereHas(
                'vendor', fn (Builder $v) => $v->where('slug', $request->query('vendeur'))->orWhere('id', $request->query('vendeur'))
            ))
            ->when($request->query('dispo') === 'promo', fn (Builder $q) => $q->whereNotNull('sale_price')->whereRaw('sale_price < price'))
            ->when($request->filled('couleur'), fn (Builder $q) => $q->whereHas('variants', function (Builder $v) use ($request) {
                $color = $request->query('couleur');
                $v->where('is_active', true)
                    ->where(function ($sub) use ($color) {
                        $sub->where('option_values->couleur', $color)
                            ->orWhere('option_values->Couleur', $color)
                            ->orWhere('option_values->color', $color)
                            ->orWhere('option_values->Color', $color);
                    });
            }));

        // Facettes : uniquement les clés déclarées « filtrables » sur la catégorie choisie.
        $filterableKeys = $category ? $this->schema->filterableKeys($category) : [];
        foreach ($facets as $key => $value) {
            if (in_array($key, $filterableKeys, true)) {
                $offers->where("custom_fields->{$key}", $value);
            }
        }

        $offers = match ($request->query('tri')) {
            'prix_asc' => $offers->orderByRaw('coalesce(sale_price, price) asc'),
            'prix_desc' => $offers->orderByRaw('coalesce(sale_price, price) desc'),
            'populaire' => $offers->orderByDesc('sales_count'),
            default => $offers->latest('first_published_at'),
        };

        // Si un pôle est sélectionné, on expose ses sous-catégories directes dans le filtre
        $displayedCategories = $activePoleCategory
            ? $activePoleCategory->children()->active()->orderBy('position')->get()
            : MarketplaceCategory::active()->roots()->orderBy('position')->get();

        $facetFields = $category ? $this->schema->schemaFor($category)->where('is_filterable', true)->values() : collect();
        $vendors = VendorProfile::active()->select('id', 'display_name', 'slug')->orderBy('display_name')->get();

        $categoryCounts = MarketplaceOffer::publiclyVisible()
            ->groupBy('category_id')
            ->selectRaw('category_id, count(*) as count')
            ->pluck('count', 'category_id')
            ->all();

        $priceBounds = [
            'min' => (int) (MarketplaceOffer::publiclyVisible()->min('price') ?: 0),
            'max' => (int) (MarketplaceOffer::publiclyVisible()->max('price') ?: 150000),
        ];

        $availableColors = MarketplaceOfferVariant::query()
            ->whereHas('offer', fn ($q) => $q->publiclyVisible())
            ->where('is_active', true)
            ->get()
            ->map(fn ($v) => $v->option_values['couleur'] ?? $v->option_values['Couleur'] ?? $v->option_values['color'] ?? $v->option_values['Color'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $poles = [
            [
                'key' => 'all',
                'slug' => null,
                'label' => 'Tous les univers',
                'icon' => '✦',
                'badge' => 'Catalogue complet',
            ],
            [
                'key' => 'fashion_beauty',
                'slug' => 'mode-beaute-cosmetiques',
                'label' => 'Mode, Beauté & Cosmétiques',
                'icon' => '👗',
                'badge' => 'Prêt-à-porter & Soins bio',
            ],
            [
                'key' => 'real_estate_hospitality',
                'slug' => 'residences-guest-houses-immobilier',
                'label' => 'Résidences, Guest Houses & Immobilier',
                'icon' => '🏡',
                'badge' => 'Hébergements & Domaines',
            ],
            [
                'key' => 'services_wellness',
                'slug' => 'services-praticiens-specialises',
                'label' => 'Services & Praticiens',
                'icon' => '💼',
                'badge' => 'Consultations & Soins',
            ],
            [
                'key' => 'digital_academy',
                'slug' => 'savoirs-formations-en-ligne',
                'label' => 'Savoirs & Formations',
                'icon' => '📚',
                'badge' => 'Ebooks & Masterclasses',
            ],
        ];

        return Inertia::render('Marketplace/Shop', [
            'offers' => $offers->paginate(18)->withQueryString()->through(fn (MarketplaceOffer $o) => $this->cardPayload($o)),
            'rootCategories' => $displayedCategories->map(fn ($c) => [
                'id' => $c->id,
                'slug' => $c->slug,
                'name' => $c->name,
                'count' => $categoryCounts[$c->id] ?? 0,
            ]),
            'category' => $category ? ['id' => $category->id, 'slug' => $category->slug, 'name' => $category->name, 'description' => $category->description] : null,
            'poles' => $poles,
            'activePole' => $activePoleSlug,
            'activePoleCategory' => $activePoleCategory ? [
                'id' => $activePoleCategory->id,
                'name' => $activePoleCategory->name,
                'slug' => $activePoleCategory->slug,
                'description' => $activePoleCategory->description,
            ] : null,
            'facetFields' => $facetFields->map(fn ($f) => ['key' => $f->key, 'label' => $f->label, 'options' => $f->options ?? []])->values(),
            'activeType' => $type,
            'activeFacets' => $facets,
            'vendors' => $vendors->map(fn ($v) => ['id' => $v->id, 'name' => $v->display_name, 'slug' => $v->slug]),
            'priceBounds' => $priceBounds,
            'availableColors' => $availableColors,
            'query' => $request->only(['q', 'prix_min', 'prix_max', 'region', 'vendeur', 'dispo', 'tri', 'couleur', 'pole']),
        ]);
    }

    public function offer(string $slug): Response
    {
        $offer = MarketplaceOffer::query()
            ->publiclyVisible()
            ->with('vendor', 'category')
            ->where('slug', $slug)
            ->firstOrFail();

        $offer->load($offer->type);

        if ($offer->type === 'physical') {
            $offer->load(['variants' => fn ($q) => $q->where('is_active', true)]);
        }

        $related = MarketplaceOffer::query()
            ->publiclyVisible()
            ->where('category_id', $offer->category_id)
            ->whereKeyNot($offer->id)
            ->with('vendor:id,display_name,slug')
            ->limit(4)
            ->get();

        // Une ligne de commande payée de cet acheteur pour cette offre, pas
        // encore notée : rend le formulaire d'avis disponible.
        $reviewableItem = null;
        if ($user = request()->user()) {
            $reviewableItem = MarketplaceOrderItem::query()
                ->where('offer_id', $offer->id)
                ->whereHas('suborder.order', fn ($q) => $q->where('buyer_id', $user->id)->where('payment_status', 'paid'))
                ->whereNotIn('id', MarketplaceReview::where('user_id', $user->id)->pluck('marketplace_order_item_id'))
                ->first();
        }

        $reviews = $offer->buyerReviews()->where('status', 'published')->latest()->limit(20)->get();
        $schema = $offer->category ? $this->schema->schemaFor($offer->category) : collect();

        return Inertia::render('Marketplace/Offer', [
            'offer' => $this->offerDetailPayload($offer),
            'schema' => $schema->map(fn ($f) => ['key' => $f->key, 'label' => $f->label])->values(),
            'related' => $related->map(fn ($o) => $this->cardPayload($o)),
            'reviews' => $reviews->map(fn ($r) => [
                'id' => $r->id, 'rating' => $r->rating, 'title' => $r->title, 'body' => $r->body,
                'vendor_reply' => $r->vendor_reply, 'created_at' => $r->created_at->translatedFormat('d F Y'),
            ]),
            'reviewableItem' => $reviewableItem ? ['id' => $reviewableItem->id] : null,
        ]);
    }

    public function vendor(string $slug): Response
    {
        $vendor = VendorProfile::query()->active()->where('slug', $slug)->firstOrFail();

        $offers = MarketplaceOffer::query()
            ->publiclyVisible()
            ->where('vendor_profile_id', $vendor->id)
            ->with('category:id,name,slug')
            ->latest('first_published_at')
            ->paginate(18);

        return Inertia::render('Marketplace/Vendor', [
            'vendor' => [
                'display_name' => $vendor->display_name,
                'slug' => $vendor->slug,
                'region' => $vendor->region,
                'city' => $vendor->city,
                'bio' => $vendor->bio,
                'seo_bio' => Str::limit(strip_tags((string) $vendor->bio), 155),
                'logo_url' => $vendor->logo_path ? Storage::url($vendor->logo_path) : null,
                'banner_url' => $vendor->banner_path ? Storage::url($vendor->banner_path) : null,
            ],
            'offers' => $offers->through(fn (MarketplaceOffer $o) => $this->cardPayload($o)),
        ]);
    }

    /** Forme envoyée pour chaque carte d'offre (grille boutique, vendeur, offres liées). */
    private function cardPayload(MarketplaceOffer $offer): array
    {
        $colors = $offer->relationLoaded('variants')
            ? $offer->variants
                ->where('is_active', true)
                ->map(fn ($v) => $v->option_values['couleur'] ?? $v->option_values['Couleur'] ?? $v->option_values['color'] ?? $v->option_values['Color'] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all()
            : [];

        return [
            'id' => $offer->id,
            'slug' => $offer->slug,
            'title' => $offer->title,
            'type' => $offer->type,
            'price' => $offer->price,
            'sale_price' => $offer->sale_price,
            'effective_price' => $offer->effectivePrice(),
            'currency' => $offer->currency,
            'main_image_url' => $offer->main_image_path ? Storage::url($offer->main_image_path) : null,
            'rating_avg' => (float) $offer->rating_avg,
            'rating_count' => $offer->rating_count,
            'colors' => $colors,
            'vendor' => $offer->vendor ? [
                'display_name' => $offer->vendor->display_name,
                'slug' => $offer->vendor->slug,
                'region' => $offer->vendor->region,
            ] : null,
        ];
    }

    private function offerDetailPayload(MarketplaceOffer $offer): array
    {
        $detail = $offer->detail();
        $variants = ($offer->variants ?? collect())->map(fn (MarketplaceOfferVariant $v) => [
            'id' => $v->id,
            'options' => (object) ($v->option_values ?? []),
            'price' => $v->effectivePrice($offer),
            'stock' => $v->availableStock(),
            'image' => $v->image_path ? Storage::url($v->image_path) : null,
        ])->values();

        return [
            'id' => $offer->id,
            'slug' => $offer->slug,
            'title' => $offer->title,
            'type' => $offer->type,
            'summary' => $offer->summary,
            'description' => $offer->description,
            'seo_description' => $offer->seo_description ?: Str::limit(strip_tags($offer->summary ?: $offer->description), 155),
            'price' => $offer->price,
            'sale_price' => $offer->sale_price,
            'effective_price' => $offer->effectivePrice(),
            'currency' => $offer->currency,
            'gallery_urls' => collect([$offer->main_image_path])->merge($offer->gallery ?? [])
                ->filter()->unique()->values()->map(fn ($p) => Storage::url($p)),
            'rating_avg' => (float) $offer->rating_avg,
            'rating_count' => $offer->rating_count,
            'sales_count' => $offer->sales_count,
            'custom_fields' => $offer->custom_fields ?? [],
            'detail' => $detail ? ['stock' => $detail->stock ?? null, 'stock_reserved' => $detail->stock_reserved ?? null] : null,
            'variants' => $variants,
            'category' => $offer->category ? ['name' => $offer->category->name, 'slug' => $offer->category->slug] : null,
            'vendor' => [
                'display_name' => $offer->vendor->display_name,
                'slug' => $offer->vendor->slug,
                'region' => $offer->vendor->region,
                'logo_url' => $offer->vendor->logo_path ? Storage::url($offer->vendor->logo_path) : null,
            ],
        ];
    }
}
