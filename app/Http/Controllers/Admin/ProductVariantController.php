<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AuditLogger;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * §12 — administration des variantes d'un produit physique : taille,
 * couleur, modèle, format… Chaque variante a son SKU, son stock, son prix
 * et son image.
 */
class ProductVariantController extends Controller
{
    private const IMG_PREFIX = '/uploads/products/';

    private const IMG_EXT = ['jpg', 'jpeg', 'jpe', 'jfif', 'png', 'webp', 'avif', 'gif'];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly StockService $stock,
    ) {}

    /**
     * PUT /admin/products/{product}/variants — remplace l'ensemble des
     * variantes (crée / met à jour / supprime les absentes). Le stock des
     * variantes existantes se pilote via adjustStock, jamais ici.
     */
    public function sync(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->type === 'physical', 422, 'Seuls les produits physiques ont des variantes.');

        $data = $request->validate([
            'variants' => ['present', 'array'],
            'variants.*.id' => ['nullable', 'uuid'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.options' => ['nullable', 'array'],
            'variants.*.options.*' => ['nullable', 'string', 'max:120'],
            'variants.*.sku' => ['nullable', 'string', 'max:255', 'distinct'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_available' => ['nullable', 'integer', 'min:0'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.remove_image' => ['nullable', 'boolean'],
        ]);

        // SKU déjà pris par une AUTRE variante ?
        foreach ($data['variants'] as $i => $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }
            $clash = ProductVariant::withTrashed()
                ->where('sku', $sku)
                ->when(! empty($row['id']), fn ($q) => $q->whereKeyNot($row['id']))
                ->exists();
            if ($clash) {
                return back()->withErrors(["variants.$i.sku" => "Le SKU « {$sku} » est déjà utilisé."]);
            }
        }

        $dir = public_path(trim(self::IMG_PREFIX, '/'));
        $kept = [];

        foreach ($data['variants'] as $i => $row) {
            $attrs = [
                'name' => $row['name'],
                'options' => array_filter(
                    $row['options'] ?? [],
                    fn ($v) => $v !== null && $v !== '',
                ),
                'sku' => trim((string) ($row['sku'] ?? '')) ?: null,
                'price' => ($row['price'] ?? '') === '' ? null : $row['price'],
                'is_active' => (bool) ($row['is_active'] ?? false),
                'position' => $i,
            ];

            $file = $request->file("variants.$i.image_file");
            if ($file instanceof UploadedFile && $file->isValid()) {
                abort_unless(
                    in_array(strtolower($file->getClientOriginalExtension()), self::IMG_EXT, true),
                    422,
                    'Image de variante invalide.',
                );
            } else {
                $file = null;
            }

            $existing = ! empty($row['id']) ? $product->variants()->find($row['id']) : null;

            if ($existing) {
                if ($file) {
                    $this->deleteUpload($existing->image);
                    $attrs['image'] = $this->storeUpload($file, $dir);
                } elseif (! empty($row['remove_image'])) {
                    $this->deleteUpload($existing->image);
                    $attrs['image'] = null;
                }
                $existing->update($attrs);
                $kept[] = $existing->id;
            } else {
                if ($file) {
                    $attrs['image'] = $this->storeUpload($file, $dir);
                }
                $attrs['stock_available'] = (int) ($row['stock_available'] ?? 0);
                $kept[] = $product->variants()->create($attrs)->id;
            }
        }

        $product->variants()->whereNotIn('id', $kept)->get()->each(fn (ProductVariant $v) => $v->delete());

        $this->audit->record(
            $request->user(),
            'product.variants_synced',
            $product,
            ['count' => count($kept)],
            "Variantes de « {$product->name} » enregistrées (".count($kept).')',
            $request,
        );

        return back()->with('success', 'Variantes enregistrées.');
    }

    /**
     * POST /admin/products/variants/{variant}/stock — ajustement manuel du
     * stock disponible d'une variante (§11).
     */
    public function adjustStock(Request $request, ProductVariant $variant): RedirectResponse
    {
        $data = $request->validate([
            'stock_available' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $movement = $this->stock->adjustAvailable(
            $variant,
            (int) $data['stock_available'],
            $request->user(),
            $data['reason'] ?? null,
        );

        if ($movement === null) {
            return back()->with('success', 'Stock inchangé.');
        }

        $this->audit->record(
            $request->user(),
            'product.variant_stock_adjusted',
            $variant->product,
            ['variant' => $variant->name, 'stock_available' => (int) $data['stock_available']],
            "Stock de la variante « {$variant->name} » ajusté à {$data['stock_available']}",
            $request,
        );

        return back()->with('success', "Stock « {$variant->name} » : {$data['stock_available']} disponible(s).");
    }

    private function storeUpload(UploadedFile $file, string $dir): string
    {
        File::ensureDirectoryExists($dir);
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $ext = in_array($ext, ['jpeg', 'jpe', 'jfif'], true) ? 'jpg' : $ext;
        $name = Str::uuid()->toString().'.'.$ext;
        $file->move($dir, $name);

        return self::IMG_PREFIX.$name;
    }

    private function deleteUpload(?string $path): void
    {
        if (! $path || ! Str::startsWith($path, self::IMG_PREFIX)) {
            return;
        }
        $full = public_path(ltrim($path, '/'));
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
