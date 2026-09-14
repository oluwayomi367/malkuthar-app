<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\MarketplaceOfferVariant;
use Illuminate\Support\Collection;

/**
 * MARKETPLACE — panier de la place de marché, stocké en session.
 *
 * Structure : ['lineKey' => ['offer_id' => …, 'variant_id' => …|null, 'qty' => …]]
 * où lineKey = "{offer_id}:{variant_id ?? '_'}" — une même offre en deux
 * variantes = deux lignes.
 */
class MarketplaceCartService
{
    private const KEY = 'mp_cart';

    /** @return array<string, array{offer_id: string, variant_id: ?string, qty: int}> */
    public function raw(): array
    {
        return array_map(function ($row) {
            // Rétro-compat : ancien format ['offer_id' => qty].
            return is_array($row)
                ? ['offer_id' => $row['offer_id'], 'variant_id' => $row['variant_id'] ?? null, 'qty' => (int) $row['qty']]
                : ['offer_id' => '', 'variant_id' => null, 'qty' => (int) $row];
        }, (array) session(self::KEY, []));
    }

    public static function lineKey(string $offerId, ?string $variantId): string
    {
        return $offerId.':'.($variantId ?: '_');
    }

    public function count(): int
    {
        return array_sum(array_column($this->raw(), 'qty'));
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    public function add(string $offerId, ?string $variantId, int $qty = 1): void
    {
        $cart = $this->raw();
        $key = self::lineKey($offerId, $variantId);
        $current = $cart[$key]['qty'] ?? 0;
        $cart[$key] = ['offer_id' => $offerId, 'variant_id' => $variantId, 'qty' => max(1, $current + $qty)];
        session([self::KEY => $cart]);
    }

    public function setQuantity(string $lineKey, int $qty): void
    {
        $cart = $this->raw();

        if (! isset($cart[$lineKey])) {
            return;
        }

        if ($qty <= 0) {
            unset($cart[$lineKey]);
        } else {
            $cart[$lineKey]['qty'] = $qty;
        }

        session([self::KEY => $cart]);
    }

    public function remove(string $lineKey): void
    {
        $cart = $this->raw();
        unset($cart[$lineKey]);
        session([self::KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    /**
     * Lignes résolues (offres encore visibles + variantes actives). Les
     * lignes devenues indisponibles sont retirées silencieusement.
     *
     * @return Collection<int, array{key: string, offer: MarketplaceOffer, variant: ?MarketplaceOfferVariant, quantity: int, unit_price: float, line_total: float}>
     */
    public function lines(): Collection
    {
        $cart = $this->raw();

        if ($cart === []) {
            return collect();
        }

        $offers = MarketplaceOffer::query()
            ->publiclyVisible()
            ->with('vendor:id,display_name,slug', 'variants')
            ->whereIn('id', collect($cart)->pluck('offer_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $lines = collect();
        $dirty = false;

        foreach ($cart as $key => $row) {
            $offer = $offers->get($row['offer_id']);
            $variant = $row['variant_id']
                ? optional($offer)->variants->firstWhere('id', $row['variant_id'])
                : null;

            $unavailable = $offer === null
                || ($row['variant_id'] && (! $variant || ! $variant->is_active))
                || ($offer && $offer->hasVariants() && ! $row['variant_id']);

            if ($unavailable) {
                unset($cart[$key]);
                $dirty = true;

                continue;
            }

            $unit = $variant ? $variant->effectivePrice($offer) : $offer->effectivePrice();

            $lines->push([
                'key' => $key,
                'offer' => $offer,
                'variant' => $variant,
                'quantity' => (int) $row['qty'],
                'unit_price' => round($unit, 2),
                'line_total' => round($unit * $row['qty'], 2),
            ]);
        }

        if ($dirty) {
            session([self::KEY => $cart]);
        }

        return $lines;
    }

    public function subtotal(): float
    {
        return round($this->lines()->sum('line_total'), 2);
    }
}
