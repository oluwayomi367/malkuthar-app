<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\AffiliateLineageService;
use App\Services\AffiliateSuspensionService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * §61-§62 — Gestion des affiliés en administration.
 */
class AffiliateController extends Controller
{
    public function __construct(
        private readonly AffiliateLineageService $lineage,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $q = trim((string) $request->query('q', ''));

        $affiliates = User::query()
            ->whereNotNull('affiliate_id')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('affiliate_id', 'like', "%{$q}%")
                        ->orWhere('code_parrainage', 'like', "%{$q}%");
                });
            })
            ->withCount('children')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return \Inertia\Inertia::render('Admin/Affiliates/Index', [
            'heading' => 'Affiliés',
            'eyebrow' => 'Gestion',
            'open_label' => 'Ouvrir',
            'affiliates' => $affiliates,
            'q' => $q,
        ]);
    }

    public function show(User $user): \Inertia\Response
    {
        abort_unless($user->isAffiliate(), 404);

        $user->load('parent:id,first_name,last_name,affiliate_id,status', 'wallet');

        return \Inertia\Inertia::render('Admin/Affiliates/Show', [
            'heading' => trim("{$user->first_name} {$user->last_name}"),
            'eyebrow' => 'Affilié',
            'labels' => [
                'profile' => 'Profil',
                'sponsor' => 'Parrain',
                'lineage' => 'Lignée',
                'level1' => 'Niveau 1',
                'level3' => 'Niveau 3',
                'sales' => 'Ventes',
                'commissions' => 'Commissions',
                'withdrawals' => 'Retraits',
                'wallet_adjustment' => 'Ajustement du solde',
                'suspension' => "Suspension de {$user->first_name}",
            ],
            'affiliate' => $user,
            'lineage' => $this->lineage->levels($user),
            'children' => User::query()
                ->where('parent_id', $user->id)
                ->orderByDesc('created_at')
                ->get(['id', 'first_name', 'last_name', 'affiliate_id', 'status', 'created_at']),
            'orders' => Order::query()
                ->where('referring_affiliate_id', $user->id)
                ->withCount('items')
                ->latest()
                ->limit(30)
                ->get(),
            'commissions' => LedgerEntry::query()->where('user_id', $user->id)
                ->where('source_type', 'commission')->with('statusChanges')->latest()->limit(30)->get(),
            'withdrawals' => Withdrawal::query()->where('user_id', $user->id)->latest()->limit(30)->get(),
            'totals' => [
                'ledger_balance' => $user->wallet?->computeAvailableBalanceFromLedger() ?? 0.0,
                'commissions' => (float) LedgerEntry::query()->where('user_id', $user->id)
                    ->where('direction', 'credit')->where('source_type', 'commission')->sum('amount'),
                'withdrawn' => (float) LedgerEntry::query()->where('user_id', $user->id)
                    ->where('direction', 'debit')->whereIn('status', ['processing', 'paid'])->sum('amount'),
                'currency' => $user->wallet?->currency ?? $user->currency ?? 'XOF',
            ],
        ]);
    }

    /**
     * §63 — suspension : motif catégorisé + traitement explicite des
     * commissions en attente, des commissions disponibles et des retraits
     * en cours. Aucune donnée financière n'est supprimée.
     */
    public function suspend(Request $request, User $user, AffiliateSuspensionService $suspension): RedirectResponse
    {
        abort_unless($user->isAffiliate(), 404);
        abort_if($user->status === 'suspended', 422, 'Ce compte est déjà suspendu.');

        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(AffiliateSuspensionService::CATEGORIES))],
            'reason' => ['required', 'string', 'max:1000'],
            'treatment_pending' => ['nullable', Rule::in(['freeze', 'cancel'])],
            'treatment_available' => ['nullable', Rule::in(['freeze', 'clawback'])],
            'treatment_withdrawals' => ['nullable', Rule::in(['hold', 'rollback'])],
        ]);

        $affected = $suspension->suspend(
            $user,
            $request->user(),
            $data['category'],
            $data['reason'],
            [
                'pending' => $data['treatment_pending'] ?? 'freeze',
                'available' => $data['treatment_available'] ?? 'freeze',
                'withdrawals' => $data['treatment_withdrawals'] ?? 'hold',
            ],
            $request,
        );

        return back()->with('success', sprintf(
            'Affilié %s suspendu. Commissions en attente : %d (%s). Solde disponible : %s (%s). Retraits en cours : %d (%s).',
            $user->affiliate_id,
            $affected['pending_commissions'],
            $affected['pending_treatment'] === 'cancel' ? 'annulées' : 'gelées',
            number_format($affected['available_amount'], 0, ',', ' '),
            $affected['available_treatment'] === 'clawback' ? 'repris' : 'gelé',
            $affected['processing_withdrawals'],
            $affected['withdrawals_treatment'] === 'rollback' ? 'recrédités' : 'laissés',
        ));
    }

    public function reactivate(Request $request, User $user, AffiliateSuspensionService $suspension): RedirectResponse
    {
        abort_unless($user->isAffiliate(), 404);

        $suspension->reactivate($user, $request->user(), $request);

        return back()->with('success', "Affilié {$user->affiliate_id} réactivé.");
    }
}
