<?php

namespace App\Support;

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\Marketplace\MarketplaceShopController;
use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\VendorProfile;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

/**
 * Sections « boutique » de la page d'accueil : nouveautés, meilleures
 * ventes, promotions et créateurs. Chaque élément porte un `kind`
 * (`product` = boutique maison, `offer` = place de marché) et la même
 * forme que les cartes du catalogue correspondant.
 */
class StorefrontHome
{
    private const PER_SECTION = 8;

    public static function sections(): array
    {
        $marketplace = MarketplaceSettings::enabled();

        return [
            'newArrivals' => self::mix(
                self::products()->latest()->limit(self::PER_SECTION)->get(),
                $marketplace ? self::offers()->latest('first_published_at')->limit(self::PER_SECTION)->get() : collect(),
            ),
            'bestSellers' => self::mix(
                self::products()
                    ->withCount(['orderItems as paid_sales' => fn ($q) => $q->whereHas('order', fn ($o) => $o->where('payment_status', 'paid'))])
                    ->orderByDesc('paid_sales')
                    ->limit(self::PER_SECTION)
                    ->get()
                    ->filter(fn (Product $p) => $p->paid_sales > 0),
                $marketplace ? self::offers()->where('sales_count', '>', 0)->orderByDesc('sales_count')->limit(self::PER_SECTION)->get() : collect(),
            ),
            'promotions' => self::mix(
                self::products()->whereNotNull('promotional_price')->whereColumn('promotional_price', '<', 'price')->latest()->limit(self::PER_SECTION)->get(),
                $marketplace ? self::offers()->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price')->latest('first_published_at')->limit(self::PER_SECTION)->get() : collect(),
            ),
            'vendors' => $marketplace ? self::vendors() : [],
        ];
    }

    private static function products()
    {
        return Product::query()
            ->where('status', 'published')
            ->where('is_available', true);
    }

    private static function offers()
    {
        return MarketplaceOffer::query()
            ->publiclyVisible()
            ->with(['vendor:id,display_name,slug,region']);
    }

    /** Alterne produits maison et offres, dans la limite d'une section. */
    private static function mix($products, $offers): array
    {
        $a = $products->values()->map(fn (Product $p) => ['kind' => 'product'] + CatalogController::cardPayload($p));
        $b = $offers->values()->map(fn (MarketplaceOffer $o) => ['kind' => 'offer'] + MarketplaceShopController::cardPayload($o));

        $out = [];
        for ($i = 0; count($out) < self::PER_SECTION && ($i < $a->count() || $i < $b->count()); $i++) {
            if ($i < $a->count()) {
                $out[] = $a[$i];
            }
            if ($i < $b->count() && count($out) < self::PER_SECTION) {
                $out[] = $b[$i];
            }
        }

        return $out;
    }

    private static function vendors(): array
    {
        return VendorProfile::query()
            ->active()
            ->withCount(['offers as live_offers' => fn ($q) => $q->live()])
            ->get()
            ->filter(fn (VendorProfile $v) => $v->live_offers > 0)
            ->sortByDesc('live_offers')
            ->take(self::PER_SECTION)
            ->map(fn (VendorProfile $v) => [
                'display_name' => $v->display_name,
                'slug' => $v->slug,
                'region' => $v->region,
                'city' => $v->city,
                'logo_url' => $v->logo_path ? Storage::url($v->logo_path) : null,
                'offers_count' => $v->live_offers,
            ])
            ->values()
            ->all();
    }
}
