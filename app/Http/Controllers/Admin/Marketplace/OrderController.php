<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceOrder;
use App\Services\AuditLogger;
use App\Services\Marketplace\MarketplacePaymentConfirmationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MARKETPLACE — suivi des commandes en administration + encaissement manuel
 * (mode « à la livraison » en attendant le paiement PSP redirigé).
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly MarketplacePaymentConfirmationService $confirmation,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): Response
    {
        $orders = MarketplaceOrder::query()
            ->withCount('suborders')
            ->with('suborders:id,marketplace_order_id,vendor_profile_id')
            ->when($request->query('payment_status'), fn ($q, $s) => $q->where('payment_status', $s))
            ->when($request->query('q'), fn ($q, $term) => $q->where('order_number', 'like', "%{$term}%")
                ->orWhere('buyer_email', 'like', "%{$term}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Marketplace/Orders/Index', [
            'orders' => $orders,
            'counts' => MarketplaceOrder::query()
                ->selectRaw('payment_status, count(*) as total')->groupBy('payment_status')->pluck('total', 'payment_status'),
            'payment_status' => (string) $request->query('payment_status', ''),
            'q' => (string) $request->query('q', ''),
        ]);
    }

    public function show(MarketplaceOrder $order): Response
    {
        $order->load('buyer', 'suborders.vendor', 'suborders.items.offer');

        return Inertia::render('Admin/Marketplace/Orders/Show', compact('order'));
    }

    public function markPaid(Request $request, MarketplaceOrder $order): RedirectResponse
    {
        $this->confirmation->confirmPaid($order, $order->payment_reference);
        $this->audit->record($request->user(), 'marketplace.order.marked_paid', $order, [
            'order_number' => $order->order_number,
        ]);

        return back()->with('success', "Commande {$order->order_number} encaissée.");
    }

    public function cancel(Request $request, MarketplaceOrder $order): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);
        $this->confirmation->cancelUnpaid($order, $data['reason']);
        $this->audit->record($request->user(), 'marketplace.order.cancelled', $order, ['reason' => $data['reason']]);

        return back()->with('success', 'Commande annulée, stock libéré.');
    }
}
