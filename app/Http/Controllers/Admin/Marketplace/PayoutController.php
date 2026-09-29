<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceSuborder;
use App\Models\Marketplace\VendorPayout;
use App\Services\Marketplace\VendorPayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MARKETPLACE — file des versements + synthèse de règlement (admin).
 */
class PayoutController extends Controller
{
    public function __construct(private readonly VendorPayoutService $payouts) {}

    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', 'requested');

        $payouts = VendorPayout::query()
            ->with('vendor:id,display_name', 'account')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Synthèse : commission plateforme gagnée / à verser / versée.
        $summary = [
            'commission_earned' => (float) MarketplaceSuborder::query()
                ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid'))
                ->sum('commission_amount'),
            'vendor_pending' => (float) MarketplaceSuborder::query()
                ->where('settlement_status', 'pending')->sum('vendor_net'),
            'vendor_paid' => (float) VendorPayout::where('status', 'paid')->sum('net_amount'),
        ];

        return Inertia::render('Admin/Marketplace/Payouts/Index', compact('payouts', 'status', 'summary'));
    }

    public function approve(Request $request, VendorPayout $payout): RedirectResponse
    {
        $this->payouts->approve($payout, $request->user());

        return back()->with('success', "Versement {$payout->reference} approuvé.");
    }

    public function markPaid(Request $request, VendorPayout $payout): RedirectResponse
    {
        $data = $request->validate(['provider_reference' => ['nullable', 'string', 'max:120']]);
        $this->payouts->markPaid($payout, $request->user(), $data['provider_reference'] ?? null);

        return back()->with('success', "Versement {$payout->reference} marqué payé.");
    }

    public function markFailed(Request $request, VendorPayout $payout): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);
        $this->payouts->markFailed($payout, $request->user(), $data['reason']);

        return back()->with('success', "Versement {$payout->reference} marqué échoué, fonds restitués.");
    }
}
