<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\AuditLogger;
use App\Services\ProductSalesReport;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * §6-§18, §60 — administration du catalogue : création / modification des
 * produits (digital, physique, service), upload des visuels (§13) et
 * gestion complète du stock (§10-§12).
 */
class ProductController extends Controller
{
    /** Répertoire web des visuels produit (servi directement, sans lien symbolique). */
    private const IMAGE_PUBLIC_PREFIX = '/uploads/products/';

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly StockService $stock,
        private readonly ProductSalesReport $salesReport,
    ) {}

    public function index(Request $request): View
    {
        $type = $request->query('type');
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->when(in_array($type, ['digital', 'physical', 'service'], true), fn ($b) => $b->where('type', $type))
            ->when(in_array($status, ['draft', 'published', 'archived'], true), fn ($b) => $b->where('status', $status))
            ->when($q !== '', fn ($b) => $b->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            }))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $lowStock = Product::query()
            ->where('type', 'physical')
            ->where('status', 'published')
            ->where('stock_available', '<=', 5)
            ->orderBy('stock_available')
            ->limit(8)
            ->get(['id', 'name', 'sku', 'stock_available', 'stock_reserved']);

        $reports = $this->salesReport->forProductIds($products->pluck('id'));

        return view('admin.products.index', compact('products', 'lowStock', 'reports', 'type', 'status', 'q'));
    }

    public function create(): View
    {
        $product = new Product([
            'type' => 'digital',
            'currency' => 'XOF',
            'status' => 'draft',
            'is_available' => true,
        ]);

        return view('admin.products.form', ['product' => $product, 'movements' => collect()]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['name']);
        $data = $this->applyImages($request, $data, null);

        $product = Product::create($data);

        $this->audit->record($request->user(), 'product.created', $product, $data, "Produit « {$product->name} » créé", $request);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', "Produit « {$product->name} » créé.");
    }

    public function show(Product $product): View
    {
        if ($product->type === 'physical') {
            $product->load(['variants' => fn ($q) => $q->orderBy('position')]);
        }

        return view('admin.products.show', [
            'product' => $product,
            'movements' => $this->stockMovements($product),
            'sales' => $this->salesReport->forProduct($product),
        ]);
    }

    public function edit(Product $product): View
    {
        if ($product->type === 'service') {
            $product->load([
                'schedules' => fn ($q) => $q->orderBy('weekday')->orderBy('start_time'),
                'timeOff' => fn ($q) => $q->orderBy('date'),
            ]);
        }

        if ($product->type === 'physical') {
            $product->load(['variants' => fn ($q) => $q->orderBy('position')]);
        }

        return view('admin.products.form', [
            'product' => $product,
            'movements' => $this->stockMovements($product),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = isset($data['slug']) && $data['slug'] !== ''
            ? $this->uniqueSlug($data['slug'], $data['name'], $product->id)
            : $product->slug;

        // Le stock physique se pilote via le panneau dédié, jamais par ce formulaire.
        unset($data['stock_available']);

        $data = $this->applyImages($request, $data, $product);

        $product->update($data);

        $this->audit->record($request->user(), 'product.updated', $product, $data, "Produit « {$product->name} » modifié", $request);

        return back()->with('success', 'Produit mis à jour.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $product->update(['status' => 'archived', 'is_available' => false]);

        $this->audit->record($request->user(), 'product.archived', $product, [], "Produit « {$product->name} » archivé", $request);

        return redirect()->route('admin.products.index')->with('success', "Produit « {$product->name} » archivé.");
    }

    /**
     * Suppression du produit (soft delete — la ligne reste en base pour
     * préserver l'historique des commandes et permettre une restauration).
     */
    public function delete(Request $request, Product $product): RedirectResponse
    {
        $name = $product->name;

        $product->delete();

        $this->audit->record($request->user(), 'product.deleted', $product, [], "Produit « {$name} » supprimé", $request);

        return redirect()->route('admin.products.index')->with('success', "Produit « {$name} » supprimé.");
    }

    /**
     * §11 — ajustement manuel du stock disponible (réappro / inventaire).
     */
    public function adjustStock(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->type === 'physical', 422, 'Seuls les produits physiques ont un stock.');

        $data = $request->validate([
            'stock_available' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $movement = $this->stock->adjustAvailable(
            $product,
            (int) $data['stock_available'],
            $request->user(),
            $data['reason'] ?? null,
        );

        if ($movement === null) {
            return back()->with('success', 'Stock inchangé.');
        }

        $this->audit->record(
            $request->user(),
            'product.stock_adjusted',
            $product,
            ['stock_available' => (int) $data['stock_available'], 'reason' => $data['reason'] ?? null],
            "Stock de « {$product->name} » ajusté à {$data['stock_available']}",
            $request,
        );

        return back()->with('success', "Stock mis à jour : {$data['stock_available']} unité(s) disponible(s).");
    }

    private function stockMovements(Product $product)
    {
        return $product->type === 'physical'
            ? StockMovement::query()
                ->where('product_id', $product->id)
                ->with('admin:id,first_name,last_name')
                ->latest()
                ->limit(50)
                ->get()
            : collect();
    }

    /**
     * §13 — applique les uploads d'images au tableau de données avant
     * persistance. Retire d'abord les clés techniques du formulaire pour
     * qu'elles ne finissent jamais en base.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyImages(ProductRequest $request, array $data, ?Product $product): array
    {
        unset($data['main_image_file'], $data['remove_main_image'], $data['gallery_files'], $data['gallery_remove']);

        $dir = public_path(trim(self::IMAGE_PUBLIC_PREFIX, '/'));

        // --- Image principale ---
        if ($request->hasFile('main_image_file')) {
            $this->deleteUpload($product?->main_image);
            $data['main_image'] = $this->storeUpload($request->file('main_image_file'), $dir);
        } elseif ($request->boolean('remove_main_image')) {
            $this->deleteUpload($product?->main_image);
            $data['main_image'] = null;
        }

        // --- Galerie ---
        $gallery = array_values(array_filter((array) ($product?->gallery ?? [])));
        $toRemove = array_filter((array) $request->input('gallery_remove', []));

        if ($toRemove) {
            $gallery = array_values(array_filter($gallery, function ($path) use ($toRemove) {
                if (in_array($path, $toRemove, true)) {
                    $this->deleteUpload($path);

                    return false;
                }

                return true;
            }));
        }

        $added = false;
        foreach ((array) $request->file('gallery_files', []) as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $gallery[] = $this->storeUpload($file, $dir);
                $added = true;
            }
        }

        if ($added || $toRemove) {
            $data['gallery'] = $gallery;
        }

        return $data;
    }

    private function storeUpload(UploadedFile $file, string $dir): string
    {
        File::ensureDirectoryExists($dir);

        $ext = strtolower($file->getClientOriginalExtension() ?: ($file->extension() ?: 'jpg'));
        $ext = match ($ext) {
            'jpeg', 'jpe', 'jfif' => 'jpg',
            default => $ext,
        };
        $name = Str::uuid()->toString().'.'.$ext;
        $file->move($dir, $name);

        return self::IMAGE_PUBLIC_PREFIX.$name;
    }

    private function deleteUpload(?string $path): void
    {
        if (! $path || ! Str::startsWith($path, self::IMAGE_PUBLIC_PREFIX)) {
            return;
        }

        $full = public_path(ltrim($path, '/'));

        if (is_file($full)) {
            @unlink($full);
        }
    }

    private function uniqueSlug(?string $slug, string $fallback, ?string $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $fallback) ?: 'produit';
        $candidate = $base;
        $i = 2;

        while (Product::query()
            ->where('slug', $candidate)
            ->when($ignoreId, fn ($b) => $b->whereKeyNot($ignoreId))
            ->exists()) {
            $candidate = "{$base}-{$i}";
            $i++;
        }

        return $candidate;
    }
}
