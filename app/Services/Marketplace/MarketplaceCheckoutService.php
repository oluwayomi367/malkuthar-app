<?php

namespace App\Services\Marketplace;

use App\Exceptions\InsufficientStockException;
use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\MarketplaceOfferVariant;
use App\Models\Marketplace\MarketplaceOrder;
use App\Models\Marketplace\MarketplaceSuborder;
use App\Models\User;
use App\Support\Sequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * MARKETPLACE — transforme un panier en commande.
 *
 *  - valide que chaque offre (et variante) est encore disponible et en stock ;
 *  - scinde le panier par vendeur en sous-commandes ;
 *  - FIGE le taux de commission sur chaque sous-commande ;
 *  - réserve le stock (offre OU variante).
 *
 * Ne distribue AUCUNE commission d'affiliation.
 */
class MarketplaceCheckoutService
{
    public function __construct(
        private readonly MarketplaceCommissionResolver $commission,
        private readonly Sequence $sequence,
    ) {}

    /**
     * @param  array<mixed>  $cart  soit ['offer_id' => qty], soit
     *                              ['key' => ['offer_id','variant_id','qty']]
     * @param  array{email: string, phone?: ?string, address?: ?array, provider?: string}  $buyer
     */
    public function place(array $cart, array $buyer, ?User $user = null): MarketplaceOrder
    {
        $rows = $this->normalize($cart);

        if ($rows === []) {
            throw ValidationException::withMessages(['cart' => 'Votre panier est vide.']);
        }

        return DB::transaction(function () use ($rows, $buyer, $user) {
            $offerIds = collect($rows)->pluck('offer_id')->unique()->all();

            $offers = MarketplaceOffer::query()
                ->publiclyVisible()
                ->with('vendor', 'category', 'physical', 'variants')
                ->whereIn('id', $offerIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($offers->count() !== count($offerIds)) {
                throw ValidationException::withMessages(['cart' => "Une offre de votre panier n'est plus disponible."]);
            }

            $currency = $offers->first()->currency;
            if ($offers->pluck('currency')->unique()->count() > 1) {
                throw ValidationException::withMessages(['cart' => 'Les offres doivent partager la même devise.']);
            }

            $orderNumber = 'MP-'.str_pad((string) $this->sequence->next('marketplace_order'), 6, '0', STR_PAD_LEFT);

            $order = MarketplaceOrder::create([
                'order_number' => $orderNumber,
                'buyer_id' => $user?->getKey(),
                'buyer_email' => $buyer['email'],
                'buyer_phone' => $buyer['phone'] ?? $user?->phone,
                'currency' => $currency,
                'payment_provider' => $buyer['provider'] ?? 'cod',
                'payment_reference' => $orderNumber,
                'shipping_address' => $buyer['address'] ?? null,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            // Résolution des lignes.
            $resolved = [];
            foreach ($rows as $row) {
                $offer = $offers->get($row['offer_id']);
                $variant = null;

                if ($offer->hasVariants()) {
                    $variant = $row['variant_id']
                        ? $offer->variants->firstWhere('id', $row['variant_id'])
                        : null;

                    if (! $variant || ! $variant->is_active) {
                        throw ValidationException::withMessages([
                            'cart' => "Choisissez une variante disponible pour « {$offer->title} ».",
                        ]);
                    }
                }

                $this->assertStock($offer, $variant, $row['qty']);
                $resolved[] = ['offer' => $offer, 'variant' => $variant, 'qty' => $row['qty']];
            }

            $grand = 0.0;
            $suborderIndex = 0;

            foreach (collect($resolved)->groupBy(fn ($r) => $r['offer']->vendor_profile_id) as $vendorRows) {
                $vendor = $vendorRows->first()['offer']->vendor;
                $suborderIndex++;

                $sub = new MarketplaceSuborder([
                    'suborder_number' => $orderNumber.'-'.$suborderIndex,
                    'vendor_profile_id' => $vendor->id,
                    'settlement_status' => 'pending',
                    'fulfilment_status' => 'unfulfilled',
                ]);
                $sub->order()->associate($order);

                $itemsTotal = 0.0;
                $itemRows = [];

                foreach ($vendorRows as $r) {
                    /** @var MarketplaceOffer $offer */
                    $offer = $r['offer'];
                    /** @var ?MarketplaceOfferVariant $variant */
                    $variant = $r['variant'];
                    $qty = $r['qty'];

                    $unit = round($variant ? $variant->effectivePrice($offer) : $offer->effectivePrice(), 2);
                    $lineTotal = round($unit * $qty, 2);
                    $itemsTotal += $lineTotal;

                    $itemRows[] = [
                        'offer_id' => $offer->id,
                        'offer_variant_id' => $variant?->id,
                        'type' => $offer->type,
                        'title' => $offer->title,
                        'variant_label' => $variant?->optionsLabel(),
                        'unit_price' => $unit,
                        'quantity' => $qty,
                        'line_total' => $lineTotal,
                        'custom_fields' => $offer->custom_fields,
                        'fulfilment_status' => 'unfulfilled',
                    ];
                }

                $gross = round($itemsTotal, 2);
                $first = $vendorRows->first()['offer'];

                $fee = $this->commission->computeFee($vendor, $first->category, $first->type, $gross);

                $sub->fill([
                    'items_total' => $gross,
                    'vendor_gross' => $gross,
                    'commission_rule_id' => $fee['rule_id'],
                    'commission_rate' => $fee['rate'],
                    'commission_amount' => $fee['amount'],
                    'commission_source' => $fee['source'],
                    'vendor_net' => round($gross - $fee['amount'], 2),
                ])->save();

                $sub->items()->createMany($itemRows);

                // Réservation du stock.
                foreach ($vendorRows as $r) {
                    if ($r['offer']->type !== 'physical') {
                        continue;
                    }
                    if ($r['variant']) {
                        $r['variant']->increment('stock_reserved', $r['qty']);
                    } elseif ($r['offer']->physical) {
                        $r['offer']->physical->increment('stock_reserved', $r['qty']);
                    }
                }

                $grand += $gross;
            }

            $order->update(['items_total' => round($grand, 2), 'grand_total' => round($grand, 2)]);

            return $order->refresh();
        });
    }

    /**
     * @param  array<mixed>  $cart
     * @return array<int, array{offer_id: string, variant_id: ?string, qty: int}>
     */
    private function normalize(array $cart): array
    {
        $rows = [];

        foreach ($cart as $key => $value) {
            if (is_array($value)) {
                $qty = (int) ($value['qty'] ?? $value['quantity'] ?? 0);
                if ($qty > 0 && ! empty($value['offer_id'])) {
                    $rows[] = ['offer_id' => (string) $value['offer_id'], 'variant_id' => $value['variant_id'] ?? null, 'qty' => $qty];
                }
            } elseif ((int) $value > 0) {
                $rows[] = ['offer_id' => (string) $key, 'variant_id' => null, 'qty' => (int) $value];
            }
        }

        return $rows;
    }

    private function assertStock(MarketplaceOffer $offer, ?MarketplaceOfferVariant $variant, int $qty): void
    {
        if ($offer->type !== 'physical') {
            return;
        }

        if ($variant) {
            if ($qty > $variant->availableStock()) {
                throw new InsufficientStockException(
                    "Stock insuffisant pour « {$offer->title} » ({$variant->optionsLabel()}) : {$variant->availableStock()} disponible(s)."
                );
            }

            return;
        }

        if (! $offer->physical) {
            return;
        }

        $available = $offer->physical->stock - $offer->physical->stock_reserved;
        if ($qty > $available) {
            throw new InsufficientStockException(
                "Stock insuffisant pour « {$offer->title} » ({$available} disponible(s))."
            );
        }
    }
}
