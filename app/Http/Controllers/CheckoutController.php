<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * CheckoutController — reçoit un panier (potentiellement mixte digital /
 * physique / service), délègue la validation dynamique à CheckoutRequest
 * (§21 - checkout intelligent) et la création de la commande à
 * CheckoutService.
 *
 * Le contrôleur reste volontairement fin : toute la logique métier vit
 * dans CheckoutService, conformément aux règles du projet.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService
    ) {
    }

    /**
     * POST /checkout
     *
     * Crée la commande à partir du panier validé. Le paiement lui-même
     * n'est PAS confirmé ici : la commande est créée avec
     * payment_status=pending, en attente de la redirection/du webhook du
     * PSP choisi (FedaPay/CinetPay - Phase 3, §26).
     */
    public function store(CheckoutRequest $request): JsonResponse
    {
        try {
            $order = $this->checkoutService->createOrderFromCheckout(
                data: $request->validated(),
                user: $request->user(),
                productsById: $request->productsById(),
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => "La commande n'a pas pu être créée. Veuillez réessayer.",
            ], 422);
        }

        return response()->json([
            'message' => 'Commande créée avec succès. En attente de confirmation du paiement.',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'currency' => $order->currency,
                'subtotal' => $order->subtotal,
                'shipping_fee' => $order->shipping_fee,
                'total_amount' => $order->total_amount,
                'payment_status' => $order->payment_status,
                'cart_composition' => [
                    'has_digital_items' => $request->hasDigitalItems(),
                    'has_physical_items' => $request->hasPhysicalItems(),
                    'has_service_items' => $request->hasServiceItems(),
                ],
                'items' => $order->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_type' => $item->product_type,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'reservation_datetime' => $item->reservation_datetime,
                ]),
            ],
        ], 201);
    }
}
