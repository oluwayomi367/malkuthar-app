<?php

namespace App\Http\Controllers\Marketplace;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceOrder;
use App\Services\Marketplace\MarketplaceCartService;
use App\Services\Marketplace\MarketplaceCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * MARKETPLACE — tunnel de commande de la place de marché.
 *
 * Le paiement par PSP redirigé nécessite l'élargissement du contrat
 * PaymentGateway (aujourd'hui typé sur App\Models\Order) — d'ici là, le
 * mode « à la livraison / manuel » (cod) est le mode par défaut, confirmé
 * par un administrateur depuis /admin/marketplace/orders.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly MarketplaceCartService $cart,
        private readonly MarketplaceCheckoutService $checkout,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('marketplace.cart')->with('error', 'Votre panier est vide.');
        }

        $lines = $this->cart->lines();

        return view('storefront.marketplace.checkout', [
            'lines' => $lines,
            'byVendor' => $lines->groupBy(fn ($l) => $l['offer']->vendor->display_name),
            'subtotal' => $this->cart->subtotal(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['required', 'in:cod'],
        ]);

        try {
            $order = $this->checkout->place($this->cart->raw(), [
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => array_filter([
                    'line' => $data['address_line'] ?? null,
                    'city' => $data['city'] ?? null,
                ]) ?: null,
                'provider' => 'cod',
            ], $request->user());
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $this->cart->clear();

        return redirect()->route('marketplace.checkout.confirmation', $order->order_number)
            ->with('success', 'Votre commande a bien été enregistrée.');
    }

    public function confirmation(string $number): View
    {
        $order = MarketplaceOrder::query()
            ->with('suborders.vendor:id,display_name,slug', 'suborders.items')
            ->where('order_number', $number)
            ->firstOrFail();

        return view('storefront.marketplace.confirmation', compact('order'));
    }
}
