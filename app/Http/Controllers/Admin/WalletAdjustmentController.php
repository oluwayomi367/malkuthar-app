<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\WalletAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * §49 — Correction manuelle du solde d'un affilié, exclusivement via une
 * écriture d'ajustement tracée (jamais une écriture directe sur balance_*).
 */
class WalletAdjustmentController extends Controller
{
    public function __construct(
        private readonly WalletAdjustmentService $adjustments,
        private readonly AuditLogger $audit,
    ) {}

    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAffiliate(), 404);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'direction' => ['required', Rule::in(['credit', 'debit'])],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $entry = $this->adjustments->adjust(
            admin: $request->user(),
            affiliate: $user,
            amount: (float) $data['amount'],
            direction: $data['direction'],
            reason: $data['reason'],
        );

        $this->audit->record(
            admin: $request->user(),
            action: 'wallet.adjusted',
            subject: $user,
            changes: ['direction' => $data['direction'], 'amount' => $data['amount'], 'ledger_entry_id' => $entry->id],
            description: "Ajustement {$data['direction']} de {$data['amount']} sur le wallet de {$user->affiliate_id} : {$data['reason']}",
            request: $request,
        );

        return back()->with('success', 'Ajustement enregistré.');
    }
}
