<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Catalog;
use App\Support\StorefrontHome;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * §73-§74 — vitrine publique : accueil, catalogue et fiche produit. URLs
 * propres via le slug. Seuls les produits publiés et disponibles sont
 * exposés au public.
 *
 * Rendu via Inertia/React (voir resources/js/Pages/{Home,Shop/*}) — la
 * vitrine ne recharge plus la page à chaque navigation. Le panier,
 * l'admin et les espaces vendeur/affilié restent en Blade, inchangés.
 */
class CatalogController extends Controller
{
    /**
     * GET / — Page d'accueil « boutique » : nouveautés, meilleures ventes,
     * promotions et créateurs (boutique maison + place de marché).
     */
    public function home(): Response
    {
        return Inertia::render('Home', [
            'categories' => Catalog::categories(),
            ...StorefrontHome::sections(),
        ]);
    }

    /**
     * GET /boutique — Grille filtrable par nature / catégorie / tri.
     */
    public function index(Request $request): Response
    {
        $type = $request->query('type');
        $category = $request->query('category');
        $sort = $request->query('sort', 'recent');
        $q = $request->query('q');

        $products = Product::query()
            ->where('status', 'published')
            ->where('is_available', true)
            ->with(['variants:id,product_id,options,is_active'])
            ->when(in_array($type, ['digital', 'physical', 'service'], true), fn ($query) => $query->where('type', $type))
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($q, fn ($query) => $query->where(fn ($sub) => $sub->where('name', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")
                ->orWhere('category', 'like', "%{$q}%")))
            ->when($request->filled('prix_min'), fn ($query) => $query->whereRaw('COALESCE(promotional_price, price) >= ?', [(float) $request->query('prix_min')]))
            ->when($request->filled('prix_max'), fn ($query) => $query->whereRaw('COALESCE(promotional_price, price) <= ?', [(float) $request->query('prix_max')]))
            ->when($request->query('dispo') === 'promo', fn ($query) => $query->whereNotNull('promotional_price')->whereRaw('promotional_price < price'))
            ->when($request->filled('couleur'), fn ($query) => $query->whereHas('variants', function ($v) use ($request) {
                $color = $request->query('couleur');
                $v->where('is_active', true)
                    ->where(function ($sub) use ($color) {
                        $sub->where('options->couleur', $color)
                            ->orWhere('options->Couleur', $color)
                            ->orWhere('options->color', $color)
                            ->orWhere('options->Color', $color);
                    });
            }))
            ->when($sort === 'price_asc', fn ($query) => $query->orderByRaw('COALESCE(promotional_price, price) asc'))
            ->when($sort === 'price_desc', fn ($query) => $query->orderByRaw('COALESCE(promotional_price, price) desc'))
            ->when($sort === 'recent', fn ($query) => $query->orderByDesc('created_at'))
            ->paginate(24)
            ->withQueryString()
            ->through(fn (Product $p) => $this->cardPayload($p));

        $categories = Product::query()
            ->where('status', 'published')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $categoriesWithCount = Product::query()
            ->where('status', 'published')
            ->whereNotNull('category')
            ->groupBy('category')
            ->selectRaw('category, count(*) as count')
            ->pluck('count', 'category')
            ->all();

        $priceBounds = [
            'min' => (int) (Product::where('status', 'published')->min('price') ?: 0),
            'max' => (int) (Product::where('status', 'published')->max('price') ?: 100000),
        ];

        $availableColors = \App\Models\ProductVariant::query()
            ->whereHas('product', fn ($q) => $q->where('status', 'published')->where('is_available', true))
            ->where('is_active', true)
            ->get()
            ->map(fn ($v) => $v->options['couleur'] ?? $v->options['Couleur'] ?? $v->options['color'] ?? $v->options['Color'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return Inertia::render('Shop/Index', [
            'products' => $products,
            'categories' => $categories,
            'categoriesWithCount' => $categoriesWithCount,
            'priceBounds' => $priceBounds,
            'availableColors' => $availableColors,
            'type' => $type,
            'category' => $category,
            'sort' => $sort,
            'q' => $q,
            'query' => $request->only(['q', 'prix_min', 'prix_max', 'dispo', 'type', 'category', 'sort', 'couleur']),
        ]);
    }

    /**
     * GET /produits/{slug} — Fiche produit (gabarit choisi par le type).
     */
    public function show(string $slug): Response
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with($this->relationsFor($slug))
            ->firstOrFail();

        // Gabarit choisi par le type : Pages/Shop/Product/{Digital|Physical|Service}.jsx
        $component = ucfirst($product->type);

        return Inertia::render("Shop/Product/{$component}", [
            'product' => $this->productPayload($product),
        ]);
    }

    /** Les variantes ne sont utiles (et ne coûtent une requête) que pour un produit physique. */
    private function relationsFor(string $slug): array
    {
        $type = Product::where('slug', $slug)->value('type');

        return $type === 'physical' ? ['variants'] : [];
    }

    public static function cardPayload(Product $p): array
    {
        $colors = $p->relationLoaded('variants')
            ? $p->variants
                ->where('is_active', true)
                ->map(fn ($v) => $v->options['couleur'] ?? $v->options['Couleur'] ?? $v->options['color'] ?? $v->options['Color'] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all()
            : [];

        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'name' => $p->name,
            'type' => $p->type,
            'category' => $p->category,
            'main_image' => $p->main_image,
            'price' => $p->price,
            'promotional_price' => $p->promotional_price,
            'currency' => $p->currency,
            'is_affiliable' => $p->is_affiliable,
            'colors' => $colors,
        ];
    }

    private function productPayload(Product $product): array
    {
        $base = [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'description' => $product->description,
            'description_text' => Str::limit(strip_tags((string) $product->description), 500),
            'seo_description' => Str::limit(strip_tags((string) $product->description), 155),
            'type' => $product->type,
            'category' => $product->category,
            'sub_category' => $product->sub_category,
            'main_image' => $product->main_image,
            'gallery' => $product->gallery ?? [],
            'presentation_video' => $product->presentation_video,
            'price' => $product->price,
            'promotional_price' => $product->promotional_price,
            'currency' => $product->currency,
            'is_available' => $product->is_available,
            'is_affiliable' => $product->is_affiliable,
            'sku' => $product->sku,
        ];

        return match ($product->type) {
            'physical' => $base + [
                'stock_available' => $product->stock_available,
                'weight' => $product->weight,
                'variants' => $product->variants->where('is_active', true)->values()->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'sku' => $v->sku,
                    'options' => (object) ($v->options ?? []),
                    'price' => $v->effectivePrice(),
                    'stock' => (int) $v->stock_available,
                    'image' => $v->image ? asset(ltrim($v->image, '/')) : null,
                ]),
            ],
            'service' => $base + [
                'duration_minutes' => $product->duration_minutes,
                'location' => $product->location,
                'delivery_mode' => $product->delivery_mode,
                'meeting_link' => $product->meeting_link,
            ],
            default => $base,
        };
    }
}
