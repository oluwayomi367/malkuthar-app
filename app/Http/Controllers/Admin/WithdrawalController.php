<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\AuditLogger;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * §54, §58 — Suivi des retraits par MALKUTHAR : marquer un retrait
 * PROCESSING comme payé ou échoué (le service gère le wallet et le ledger).
 */
class WithdrawalController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawals,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $status = $request->query('status');

        $withdrawals = Withdrawal::query()
            ->with('user:id,first_name,last_name,affiliate_id,email')
            ->when(in_array($status, ['processing', 'paid', 'failed', 'cancelled'], true), fn ($q) => $q->where('status', $status))
            ->latest('requested_at')
            ->paginate(30)
            ->withQueryString();

        return \Inertia\Inertia::render('Admin/Withdrawals/Index', [
            'heading' => 'Demandes de retrait',
            'eyebrow' => 'Finances',
            'withdrawals' => $withdrawals,
            'status' => $status,
        ]);
    }

    public function markPaid(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $ref = $request->validate(['provider_reference' => ['nullable', 'string', 'max:120']])['provider_reference'] ?? null;

        $this->withdrawals->markWithdrawalAsPaid($withdrawal, $ref);

        $this->audit->record($request->user(), 'withdrawal.marked_paid', $withdrawal, ['provider_reference' => $ref],
            "Retrait {$withdrawal->reference} marqué payé", $request);

        return back()->with('success', "Retrait {$withdrawal->reference} marqué payé.");
    }

    public function markFailed(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:255']])['reason'];

        $this->withdrawals->rollbackFailedWithdrawal($withdrawal, $reason);

        $this->audit->record($request->user(), 'withdrawal.marked_failed', $withdrawal, ['reason' => $reason],
            "Retrait {$withdrawal->reference} marqué échoué : {$reason}", $request);

        return back()->with('success', "Retrait {$withdrawal->reference} marqué échoué, fonds recrédités.");
    }
}
