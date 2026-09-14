<?php

namespace App\Http\Controllers;

use App\Models\Product;
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
     * GET / — Page d'accueil : présentation de la maison, sans catalogue.
     * Les produits vivent exclusivement sur /boutique.
     */
    public function home(): Response
    {
        return Inertia::render('Home');
    }

    /**
     * GET /boutique — Grille filtrable par nature / catégorie / tri.
     */
    public function index(Request $request): Response
    {
        $type = $request->query('type');
        $category = $request->query('category');
        $sort = $request->query('sort', 'recent');

        $products = Product::query()
            ->where('status', 'published')
            ->where('is_available', true)
            ->when(in_array($type, ['digital', 'physical', 'service'], true), fn ($q) => $q->where('type', $type))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($sort === 'price_asc', fn ($q) => $q->orderByRaw('COALESCE(promotional_price, price) asc'))
            ->when($sort === 'price_desc', fn ($q) => $q->orderByRaw('COALESCE(promotional_price, price) desc'))
            ->when($sort === 'recent', fn ($q) => $q->orderByDesc('created_at'))
            ->paginate(24)
            ->withQueryString()
            ->through(fn (Product $p) => $this->cardPayload($p));

        $categories = Product::query()
            ->where('status', 'published')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return Inertia::render('Shop/Index', [
            'products' => $products,
            'categories' => $categories,
            'type' => $type,
            'category' => $category,
            'sort' => $sort,
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

    private function cardPayload(Product $p): array
    {
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
