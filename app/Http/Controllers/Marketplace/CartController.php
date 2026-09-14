<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceOffer;
use App\Services\Marketplace\MarketplaceCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MARKETPLACE — panier (session) de la place de marché.
 */
class CartController extends Controller
{
    public function __construct(private readonly MarketplaceCartService $cart) {}

    public function show(): View
    {
        $lines = $this->cart->lines();

        return view('storefront.marketplace.cart', [
            'lines' => $lines,
            'byVendor' => $lines->groupBy(fn ($l) => $l['offer']->vendor->display_name),
            'subtotal' => $this->cart->subtotal(),
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'offer_id' => ['required', 'exists:marketplace_offers,id'],
            'variant_id' => ['nullable', 'exists:marketplace_offer_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $offer = MarketplaceOffer::publiclyVisible()->with('variants')->find($data['offer_id']);
        abort_if($offer === null, 404);

        $variantId = $data['variant_id'] ?? null;

        if ($offer->hasVariants()) {
            $variant = $variantId ? $offer->variants->firstWhere('id', $variantId) : null;

            if (! $variant || ! $variant->is_active) {
                return back()->with('error', 'Choisissez une option disponible avant d\'ajouter au panier.');
            }
        } else {
            $variantId = null;
        }

        $this->cart->add($offer->id, $variantId, (int) ($data['quantity'] ?? 1));

        return redirect()->route('marketplace.cart')->with('success', 'Ajouté au panier.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'line_key' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        $this->cart->setQuantity($data['line_key'], (int) $data['quantity']);

        return back()->with('success', 'Panier mis à jour.');
    }

    public function remove(Request $request): RedirectResponse
    {
        $this->cart->remove((string) $request->input('line_key'));

        return back()->with('success', 'Article retiré.');
    }
}
