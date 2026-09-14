<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Payments\PaymentVerification;
use App\Services\AuditLogger;
use App\Services\CouponService;
use App\Services\OrderRefundService;
use App\Services\Payments\PaymentConfirmationService;
use App\Services\ServiceAvailabilityService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * §60 — Administration des commandes.
 *
 * Point d'entrée unique pour suivre le cycle de vie d'une commande, quel
 * que soit son mode de paiement, et notamment pour ENCAISSER un paiement
 * à la livraison (§22/§26) : l'encaissement réutilise
 * PaymentConfirmationService::confirmPaid — donc distribution des
 * commissions (§28-§39), sortie de stock (§11) et livraison digitale (§9).
 */
class OrderController extends Controller
{
    /** Étapes de préparation/livraison d'une commande physique (§13). */
    private const FULFILMENT_STEPS = ['preparing', 'shipped', 'in_delivery', 'delivered'];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PaymentConfirmationService $confirmation,
        private readonly OrderRefundService $refunds,
        private readonly StockService $stock,
        private readonly ServiceAvailabilityService $availability,
        private readonly CouponService $coupons,
    ) {}

    public function index(Request $request): View
    {
        $paymentStatus = $request->query('payment_status');
        $provider = $request->query('provider');
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));

        $orders = Order::query()
            ->with(['items', 'user', 'referringAffiliate'])
            ->when(
                in_array($paymentStatus, ['pending', 'paid', 'failed', 'refunded', 'partially_refunded'], true),
                fn ($b) => $b->where('payment_status', $paymentStatus),
            )
            ->when(
                in_array($provider, ['fedapay', 'cinetpay', 'cod'], true),
                fn ($b) => $b->where('payment_provider', $provider),
            )
            ->when(
                in_array($status, ['pending', 'processing', 'completed', 'cancelled', 'refunded'], true),
                fn ($b) => $b->where('status', $status),
            )
            ->when($q !== '', fn ($b) => $b->where(function ($w) use ($q) {
                $w->where('order_number', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u
                        ->where('email', 'like', "%{$q}%")
                        ->orWhere('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%"));
            }))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $codToCollect = Order::query()
            ->where('payment_provider', 'cod')
            ->where('payment_status', 'pending')
            ->where('status', '!=', 'cancelled');

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => compact('paymentStatus', 'provider', 'status', 'q'),
            'kpi' => [
                'cod_count' => (clone $codToCollect)->count(),
                'cod_amount' => (float) (clone $codToCollect)->sum('total_amount'),
                'to_prepare' => Order::query()
                    ->where('payment_status', 'paid')
                    ->where('has_physical_items', true)
                    ->where(fn ($b) => $b->whereNull('shipping_status')->orWhere('shipping_status', 'preparing'))
                    ->count(),
                'revenue_today' => (float) Order::query()
                    ->where('payment_status', 'paid')
                    ->whereDate('updated_at', today())
                    ->sum('total_amount'),
            ],
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'items.product',
            'user',
            'referringAffiliate',
            'coupon',
            'codCollector',
            'ledgerEntries.user',
        ]);

        $commissions = $order->ledgerEntries
            ->whereIn('source_type', ['commission', 'commission_reversal'])
            ->sortBy(['level', fn ($e) => $e->created_at]);

        return view('admin.orders.show', [
            'order' => $order,
            'commissions' => $commissions,
            'fulfilmentSteps' => self::FULFILMENT_STEPS,
        ]);
    }

    /**
     * Encaissement d'un paiement à la livraison : bascule la commande en
     * « payé » et déclenche tous les effets d'un paiement confirmé.
     */
    public function collectCod(Request $request, Order $order): RedirectResponse
    {
        abort_unless(
            $order->isCashOnDelivery() && $order->payment_status !== 'paid' && $order->status !== 'cancelled',
            422,
            "Cette commande n'est pas un paiement à la livraison en attente.",
        );

        $this->confirmation->confirmPaid($order, new PaymentVerification(
            status: PaymentVerification::PAID,
            reference: 'COD-'.$order->order_number,
        ));

        $order->forceFill([
            'cod_collected_at' => now(),
            'cod_collected_by' => $request->user()->id,
            'shipping_status' => $order->has_physical_items && ! $order->shipping_status
                ? 'preparing'
                : $order->shipping_status,
        ])->save();

        $this->audit->record(
            $request->user(),
            'order.cod_collected',
            $order,
            ['total_amount' => (float) $order->total_amount, 'currency' => $order->currency],
            "Paiement à la livraison encaissé — commande {$order->order_number}",
            $request,
        );

        return back()->with('success', "Paiement encaissé. Commande {$order->order_number} confirmée.");
    }

    /**
     * §13 — avancement de la préparation / livraison d'une commande physique.
     */
    public function updateFulfilment(Request $request, Order $order): RedirectResponse
    {
        abort_unless(
            $order->payment_status === 'paid' && $order->has_physical_items,
            422,
            'La préparation ne concerne que les commandes physiques payées.',
        );

        $data = $request->validate([
            'shipping_status' => ['required', Rule::in(self::FULFILMENT_STEPS)],
            'shipping_carrier' => ['nullable', 'string', 'max:120'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
        ]);

        $order->update([
            'shipping_status' => $data['shipping_status'],
            'shipping_carrier' => $data['shipping_carrier'] ?? $order->shipping_carrier,
            'tracking_number' => $data['tracking_number'] ?? $order->tracking_number,
            'status' => $data['shipping_status'] === 'delivered' ? 'completed' : $order->status,
        ]);

        $this->audit->record(
            $request->user(),
            'order.fulfilment_updated',
            $order,
            $data,
            "Suivi livraison mis à jour ({$data['shipping_status']}) — commande {$order->order_number}",
            $request,
        );

        return back()->with('success', 'Suivi de livraison mis à jour.');
    }

    /**
     * Annulation d'une commande NON payée : libère le stock réservé et les
     * créneaux, rend le coupon. Une commande payée se rembourse (refund).
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_if(
            $order->payment_status === 'paid',
            422,
            'Une commande payée ne peut pas être annulée : utilisez le remboursement.',
        );
        abort_if($order->status === 'cancelled', 422, 'Commande déjà annulée.');

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($order, $data) {
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            foreach ($order->items as $item) {
                if ($item->product_type === 'physical') {
                    $this->stock->releaseReservation($item);
                }

                if ($item->product_type === 'service') {
                    $this->availability->releaseSlot($item);
                }
            }

            if ($order->coupon) {
                $this->coupons->release($order->coupon);
            }

            $order->update([
                'status' => 'cancelled',
                'payment_status' => $order->payment_status === 'pending' ? 'failed' : $order->payment_status,
                'notes' => trim(($order->notes ? $order->notes."\n" : '')
                    .'Annulation'.(! empty($data['reason']) ? " : {$data['reason']}" : '')),
            ]);
        });

        $this->audit->record(
            $request->user(),
            'order.cancelled',
            $order->refresh(),
            ['reason' => $data['reason'] ?? null],
            "Commande {$order->order_number} annulée",
            $request,
        );

        return back()->with('success', "Commande {$order->order_number} annulée.");
    }

    /**
     * §80 — remboursement d'une commande payée : reprise des commissions,
     * remise en stock, libération des créneaux, coupon rendu.
     */
    public function refund(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->payment_status === 'paid', 422, "Seule une commande payée peut être remboursée.");

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->refunds->refund($order, $data['reason'] ?? 'Remboursement administrateur');

        $this->audit->record(
            $request->user(),
            'order.refunded',
            $order->refresh(),
            ['reason' => $data['reason'] ?? null],
            "Commande {$order->order_number} remboursée",
            $request,
        );

        return back()->with('success', "Commande {$order->order_number} remboursée.");
    }
}
