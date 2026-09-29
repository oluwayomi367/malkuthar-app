<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CheckoutService — construit la commande (Order + OrderItems) à partir
 * d'un panier validé par CheckoutRequest (Phase 2).
 *
 * NB : cette étape crée la commande au statut payment_status=pending.
 * La confirmation réelle du paiement (§26 - jamais fiable côté client)
 * et la distribution des commissions (AffiliationService) interviennent
 * ensuite, déclenchées par le webhook du PSP (Phase 3).
 */
class CheckoutService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly ServiceAvailabilityService $availability,
        private readonly CouponService $coupons,
        private ?AffiliateOnboardingService $onboarding = null,
    ) {
        $this->onboarding = $onboarding ?? app(AffiliateOnboardingService::class);
    }

    /**
     * @param  User|null  $referringAffiliate  affilié dont le lien a amené
     *                                         le client (§39). Ignoré si ce n'est pas un affilié actif, ou
     *                                         si l'acheteur est lui-même cet affilié (§38 - auto-parrainage).
     */
    public function createOrderFromCheckout(
        array $data,
        ?User $user,
        Collection $productsById,
        ?User $referringAffiliate = null,
        ?array $utmData = null,
        bool $hasReferralInput = false,
    ): Order {
        return DB::transaction(function () use ($data, $user, $productsById, $referringAffiliate, $utmData, $hasReferralInput) {
            // §79 - coupon éventuel, résolu et consommé dans la transaction.
            $coupon = $this->coupons->resolve($data['coupon_code'] ?? null);
            if ($coupon && ! $this->coupons->consume($coupon)) {
                $coupon = null; // quota épuisé entre-temps
            }

            $order = Order::create([
                'user_id' => $user?->id,
                'referring_affiliate_id' => $this->resolveReferringAffiliateId($user, $referringAffiliate, $hasReferralInput),
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $data['payment_method'],
                'payment_provider' => $data['payment_provider'],
                'currency' => $this->resolveCurrency($productsById),
                'subtotal' => 0,
                'discount_total' => 0,
                'shipping_fee' => 0,
                'total_amount' => 0,
                'shipping_address' => $data['shipping_address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'utm_data' => $utmData ?: null,
            ]);

            [$subtotal, $discountTotal, $flags] = $this->createOrderItems($order, $data['items'], $productsById, $coupon);

            // §14 - Les frais de livraison réels dépendent de règles admin
            // (pays/zone/poids/méthode) qui seront branchées via un
            // ShippingFeeService dédié. Placeholder volontairement isolé ici.
            $shippingFee = $flags['has_physical_items']
                ? $this->calculateShippingFee($data['shipping_address'] ?? [])
                : 0.0;

            // §79 - le coupon a un montant minimum de commande : s'il n'est
            // pas atteint, la remise est annulée.
            if ($coupon && $coupon->min_order_amount && $subtotal < (float) $coupon->min_order_amount) {
                $this->coupons->release($coupon);
                $order->update(['coupon_id' => null, 'coupon_code' => null]);
                $this->clearDiscounts($order);
                $discountTotal = 0.0;
            }

            $order->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => round($subtotal - $discountTotal + $shippingFee, 2),
                'has_digital_items' => $flags['has_digital_items'],
                'has_physical_items' => $flags['has_physical_items'],
                'has_service_items' => $flags['has_service_items'],
            ]);

            return $order->fresh('items');
        });
    }

    /**
     * Annule les remises de ligne (coupon finalement non applicable) et
     * réaligne commission_base_amount sur le total brut.
     */
    private function clearDiscounts(Order $order): void
    {
        foreach ($order->items()->where('discount_amount', '>', 0)->get() as $item) {
            $item->update([
                'discount_amount' => 0,
                'commission_base_amount' => $item->is_affiliable_snapshot ? $item->total_price : null,
            ]);
        }
    }

    /**
     * Crée les OrderItems en snapshotant le type de produit et
     * l'éligibilité à l'affiliation AU MOMENT DE L'ACHAT (§4/§5).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: float, 1: float, 2: array{has_digital_items: bool, has_physical_items: bool, has_service_items: bool}}
     */
    private function createOrderItems(Order $order, array $items, Collection $productsById, ?Coupon $coupon): array
    {
        $subtotal = 0.0;
        $discountTotal = 0.0;
        $hasDigital = false;
        $hasPhysical = false;
        $hasService = false;

        foreach ($items as $index => $itemInput) {
            $product = $productsById->get($itemInput['product_id']);

            if (! $product) {
                // Ne devrait jamais arriver : déjà validé par CheckoutRequest
                // (exists:products,id), garde-fou défensif ici.
                throw new \RuntimeException("Produit introuvable pour la ligne #{$index}: {$itemInput['product_id']}");
            }

            // §12 - variante physique : SKU / stock / prix / image propres.
            $variant = null;
            if (! empty($itemInput['variant_id'])) {
                $variant = \App\Models\ProductVariant::query()
                    ->where('id', $itemInput['variant_id'])
                    ->where('product_id', $product->id)
                    ->where('is_active', true)
                    ->first();

                if (! $variant) {
                    throw new \RuntimeException("Variante introuvable pour la ligne #{$index}: {$itemInput['variant_id']}");
                }
            }

            $quantity = (int) $itemInput['quantity'];
            $unitPrice = $variant
                ? $variant->effectivePrice()
                : (float) ($product->promotional_price ?? $product->price);
            $totalPrice = round($unitPrice * $quantity, 2);

            // §79 - remise coupon appliquée à la ligne si le coupon la cible.
            $discountAmount = ($coupon && $coupon->appliesTo($product))
                ? $coupon->discountForLine($totalPrice)
                : 0.0;

            // §4/§5 - RÈGLE ABSOLUE : seule une ligne digital+affiliable
            // génère une base de calcul de commission.
            $isAffiliableSnapshot = $product->type === 'digital' && (bool) $product->is_affiliable;

            $reservationDatetime = null;
            if ($product->type === 'service') {
                $reservationDatetime = sprintf(
                    '%s %s:00',
                    $itemInput['reservation_date'],
                    $itemInput['reservation_time']
                );
            }

            $orderItem = $order->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'product_name' => $product->name,
                'product_type' => $product->type,
                'is_affiliable_snapshot' => $isAffiliableSnapshot,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'total_price' => $totalPrice,
                'currency' => $order->currency,
                // §5/§79 - la base de commission est le montant APRÈS remise.
                'commission_base_amount' => $isAffiliableSnapshot
                    ? round($totalPrice - $discountAmount, 2)
                    : null,
                'variant_sku' => $variant?->sku ?? ($itemInput['variant_sku'] ?? null),
                'variant_options' => $variant?->options,
                'reservation_datetime' => $reservationDatetime,
            ]);

            // §11 - réserve le stock dans la même transaction que la
            // commande : une rupture de stock annule la commande entière
            // (l'exception remonte, la transaction est annulée).
            $this->stock->reserve($orderItem);

            // §17-§18 - réserve le créneau si la ligne est un service.
            $this->availability->reserveSlot($orderItem);

            $subtotal += $totalPrice;
            $discountTotal += $discountAmount;
            $hasDigital = $hasDigital || $product->type === 'digital';
            $hasPhysical = $hasPhysical || $product->type === 'physical';
            $hasService = $hasService || $product->type === 'service';
        }

        return [round($subtotal, 2), round($discountTotal, 2), [
            'has_digital_items' => $hasDigital,
            'has_physical_items' => $hasPhysical,
            'has_service_items' => $hasService,
        ]];
    }

    /**
     * §38-§39 — N'attribue la vente à l'affilié référent que s'il est un
     * affilié actif ET différent de l'acheteur (blocage auto-parrainage).
     * Si aucun code ou cookie n'a été fourni, le parrain direct du client
     * ou à défaut le Système devient l'affilié référent d'office.
     */
    private function resolveReferringAffiliateId(?User $buyer, ?User $referringAffiliate, bool $hasReferralInput = false): ?string
    {
        if ($hasReferralInput) {
            // Un code ou cookie a été explicitement fourni
            if (! $referringAffiliate
                || ! $referringAffiliate->isAffiliate()
                || $referringAffiliate->status !== 'active') {
                return null;
            }

            if ($buyer && $buyer->getKey() === $referringAffiliate->getKey()) {
                return null; // auto-parrainage interdit
            }

            return $referringAffiliate->getKey();
        }

        // Aucun code ou cookie n'a été fourni :
        // 1. Si un affilié référent est tout de même résolu
        if ($referringAffiliate
            && $referringAffiliate->isAffiliate()
            && $referringAffiliate->status === 'active'
            && (! $buyer || $buyer->getKey() !== $referringAffiliate->getKey())) {
            return $referringAffiliate->getKey();
        }

        // 2. Si le client a un parrain direct éligible
        if ($buyer && $buyer->parent_id) {
            $parent = $buyer->parent;
            if ($parent && $parent->isAffiliate() && $parent->status === 'active' && $parent->getKey() !== $buyer->getKey()) {
                return $parent->getKey();
            }
        }

        // 3. À défaut de parrain, le Système devient d'office le parrain
        $system = $this->onboarding->systemUser();
        if ($buyer && $buyer->getKey() === $system->getKey()) {
            return null;
        }

        return $system->getKey();
    }

    private function resolveCurrency(Collection $productsById): string
    {
        return $productsById->first()->currency ?? 'XOF';
    }

    private function generateOrderNumber(): string
    {
        return 'MAL-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
    }

    /**
     * TODO Phase 2 : brancher un ShippingFeeService qui applique les
     * règles définies en administration (§14 - pays/zone/ville/poids/
     * méthode/montant). Valeur fixe temporaire pour ne pas bloquer le flux.
     */
    private function calculateShippingFee(array $shippingAddress): float
    {
        return 2500.00;
    }
}
