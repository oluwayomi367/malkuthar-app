<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\ServiceBookingException;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\Payments\PaymentInitiationService;
use App\Services\ReferralResolver;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * CheckoutController — reçoit un panier (potentiellement mixte digital /
 * physique / service), délègue la validation dynamique à CheckoutRequest
 * (§21) et la création de la commande à CheckoutService, puis initie le
 * paiement auprès du PSP choisi (§26).
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly ReferralResolver $referralResolver,
        private readonly PaymentInitiationService $paymentInitiation,
    ) {}

    /**
     * POST /checkout
     */
    public function store(CheckoutRequest $request): JsonResponse
    {
        try {
            $order = $this->checkoutService->createOrderFromCheckout(
                data: $request->validated(),
                user: $request->user(),
                productsById: $request->productsById(),
                referringAffiliate: $this->referralResolver->fromRequest(
                    $request,
                    $request->validated()['referral_code'] ?? null,
                ),
                utmData: $request->session()->get('utm'),
            );
        } catch (InsufficientStockException|ServiceBookingException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => "La commande n'a pas pu être créée. Veuillez réessayer.",
            ], 422);
        }

        $payment = $order->isCashOnDelivery()
            // Paiement à la livraison : pas de redirection PSP. La commande
            // reste `pending` jusqu'à ce qu'un administrateur encaisse.
            ? ['status' => 'cod', 'provider' => 'cod']
            : $this->initiatePayment($order, $request);

        return response()->json([
            'message' => 'Commande créée. Redirigez le client vers l\'URL de paiement.',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'currency' => $order->currency,
                'subtotal' => $order->subtotal,
                'shipping_fee' => $order->shipping_fee,
                'total_amount' => $order->total_amount,
                'payment_status' => $order->payment_status,
                'payment' => $payment,
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

    /**
     * @return array{status: string, url?: string, provider?: string, error?: string}
     */
    private function initiatePayment(Order $order, CheckoutRequest $request): array
    {
        try {
            $session = $this->paymentInitiation->initiate($order, [
                'email' => $request->input('email') ?? $request->user()?->email,
                'first_name' => $request->user()?->first_name ?? $request->input('shipping_address.first_name'),
                'last_name' => $request->user()?->last_name ?? $request->input('shipping_address.last_name'),
                'phone' => $request->input('shipping_address.phone'),
            ]);

            return [
                'status' => 'initiated',
                'provider' => $session->provider,
                'url' => $session->paymentUrl,
            ];
        } catch (Throwable $e) {
            report($e);

            // La commande existe (pending) : le paiement pourra être relancé.
            return [
                'status' => 'initiation_failed',
                'error' => "L'initiation du paiement a échoué. La commande est enregistrée et le paiement peut être relancé.",
            ];
        }
    }
}
