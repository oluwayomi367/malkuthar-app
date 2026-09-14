<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * §73-§74 — vitrine publique : accueil, catalogue et fiche produit. URLs
 * propres via le slug. Seuls les produits publiés et disponibles sont
 * exposés au public.
 */
class CatalogController extends Controller
{
    /**
     * GET / — Page d'accueil : présentation de la maison, sans catalogue.
     * Les produits vivent exclusivement sur /boutique.
     */
    public function home(): View
    {
        return view('storefront.home');
    }

    /**
     * GET /boutique — Grille filtrable par nature / catégorie / tri.
     */
    public function index(Request $request): View
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
            ->withQueryString();

        $categories = Product::query()
            ->where('status', 'published')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('storefront.shop.index', compact('products', 'categories', 'type', 'category', 'sort'));
    }

    /**
     * GET /produits/{slug} — Fiche produit (gabarit choisi par le type).
     */
    public function show(string $slug): View
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        // Gabarit choisi par le type : storefront/product/{digital|physical|service}
        return view("storefront.product.{$product->type}", compact('product'));
    }
}
