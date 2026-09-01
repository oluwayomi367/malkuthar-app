<?php

namespace App\Services;

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
    public function createOrderFromCheckout(array $data, ?User $user, Collection $productsById): Order
    {
        return DB::transaction(function () use ($data, $user, $productsById) {
            $order = Order::create([
                'user_id' => $user?->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $data['payment_method'],
                'payment_provider' => $data['payment_provider'],
                'currency' => $this->resolveCurrency($productsById),
                'subtotal' => 0,
                'shipping_fee' => 0,
                'total_amount' => 0,
                'shipping_address' => $data['shipping_address'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            [$subtotal, $flags] = $this->createOrderItems($order, $data['items'], $productsById);

            // §14 - Les frais de livraison réels dépendent de règles admin
            // (pays/zone/poids/méthode) qui seront branchées via un
            // ShippingFeeService dédié. Placeholder volontairement isolé ici.
            $shippingFee = $flags['has_physical_items']
                ? $this->calculateShippingFee($data['shipping_address'] ?? [])
                : 0.0;

            $order->update([
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => round($subtotal + $shippingFee, 2),
                'has_digital_items' => $flags['has_digital_items'],
                'has_physical_items' => $flags['has_physical_items'],
                'has_service_items' => $flags['has_service_items'],
            ]);

            return $order->fresh('items');
        });
    }

    /**
     * Crée les OrderItems en snapshotant le type de produit et
     * l'éligibilité à l'affiliation AU MOMENT DE L'ACHAT (§4/§5).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: float, 1: array{has_digital_items: bool, has_physical_items: bool, has_service_items: bool}}
     */
    private function createOrderItems(Order $order, array $items, Collection $productsById): array
    {
        $subtotal = 0.0;
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

            $quantity = (int) $itemInput['quantity'];
            $unitPrice = (float) ($product->promotional_price ?? $product->price);
            $totalPrice = round($unitPrice * $quantity, 2);

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

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_type' => $product->type,
                'is_affiliable_snapshot' => $isAffiliableSnapshot,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => 0,
                'total_price' => $totalPrice,
                'currency' => $order->currency,
                'commission_base_amount' => $isAffiliableSnapshot ? $totalPrice : null,
                'variant_sku' => $itemInput['variant_sku'] ?? null,
                'reservation_datetime' => $reservationDatetime,
            ]);

            $subtotal += $totalPrice;
            $hasDigital = $hasDigital || $product->type === 'digital';
            $hasPhysical = $hasPhysical || $product->type === 'physical';
            $hasService = $hasService || $product->type === 'service';

            // TODO Phase 2 : réservation de stock (stock_reserved) pour les
            // lignes PHYSICAL, à effectuer ici dans la même transaction
            // (§11 - gestion des stocks).
        }

        return [$subtotal, [
            'has_digital_items' => $hasDigital,
            'has_physical_items' => $hasPhysical,
            'has_service_items' => $hasService,
        ]];
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
