<?php

namespace App\Http\Controllers;

use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Product;
use App\Support\MarketplaceSettings;
use Illuminate\Http\Response;

/**
 * §74 — sitemaps générés dynamiquement.
 *
 * Le catalogue propriétaire (`/sitemap.xml`) et la place de marché
 * (`/sitemap-marketplace.xml`) sont STRICTEMENT SÉPARÉS : les offres des
 * vendeurs ne figurent jamais dans le sitemap de la boutique du propriétaire.
 */
class SitemapController extends Controller
{
    /** Catalogue propriétaire + pages institutionnelles uniquement. */
    public function firstParty(): Response
    {
        $base = rtrim((string) config('app.url'), '/');

        $urls = [
            ['loc' => $base.'/', 'priority' => '1.0'],
            ['loc' => $base.'/boutique', 'priority' => '0.9'],
            ['loc' => $base.'/a-propos', 'priority' => '0.4'],
            ['loc' => $base.'/contact', 'priority' => '0.4'],
            ['loc' => $base.'/programme-affiliation/conditions', 'priority' => '0.3'],
        ];

        Product::query()
            ->where('status', 'published')
            ->where('is_available', true)
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at'])
            ->each(function (Product $product) use (&$urls, $base) {
                $urls[] = [
                    'loc' => $base.'/produits/'.$product->slug,
                    'lastmod' => optional($product->updated_at)->toAtomString(),
                    'priority' => '0.8',
                ];
            });

        return $this->xml($urls);
    }

    /** Place de marché uniquement (offres des vendeurs + pages boutique). */
    public function marketplace(): Response
    {
        abort_unless(MarketplaceSettings::enabled(), 404);

        $base = rtrim((string) config('app.url'), '/');

        $urls = [
            ['loc' => $base.'/place-de-marche', 'priority' => '0.7'],
            ['loc' => $base.'/conditions-vendeurs', 'priority' => '0.2'],
        ];

        MarketplaceOffer::query()
            ->publiclyVisible()
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at'])
            ->each(function (MarketplaceOffer $offer) use (&$urls, $base) {
                $urls[] = [
                    'loc' => $base.'/place-de-marche/o/'.$offer->slug,
                    'lastmod' => optional($offer->updated_at)->toAtomString(),
                    'priority' => '0.6',
                ];
            });

        return $this->xml($urls);
    }

    /** @param  array<int, array<string, string|null>>  $urls */
    private function xml(array $urls): Response
    {
        return response(
            view('seo.sitemap', ['urls' => $urls])->render(),
            200,
            ['Content-Type' => 'application/xml'],
        );
    }
}
