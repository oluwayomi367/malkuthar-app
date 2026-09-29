<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\User;
use App\Models\Withdrawal;
use Inertia\Inertia;
use Inertia\Response;

/**
 * §60 — Tableau de bord administrateur.
 */
class DashboardController extends Controller
{
    public function index(): Response
    {
        $newSince = now()->subDays(30);

        $stats = [
            'affiliates_total' => User::query()->whereNotNull('affiliate_id')->count(),
            'affiliates_new' => User::query()->whereNotNull('affiliate_id')->where('affiliate_activated_at', '>=', $newSince)->count(),
            'affiliates_active' => User::query()->whereNotNull('affiliate_id')->where('status', 'active')->count(),
            'affiliates_suspended' => User::query()->whereNotNull('affiliate_id')->where('status', 'suspended')->count(),

            'orders_paid' => Order::query()->where('payment_status', 'paid')->count(),
            'revenue' => (float) Order::query()->where('payment_status', 'paid')->sum('total_amount'),

            'cod_to_collect_count' => Order::query()
                ->where('payment_provider', 'cod')->where('payment_status', 'pending')
                ->where('status', '!=', 'cancelled')->count(),
            'cod_to_collect_amount' => (float) Order::query()
                ->where('payment_provider', 'cod')->where('payment_status', 'pending')
                ->where('status', '!=', 'cancelled')->sum('total_amount'),

            'commissions_generated' => (float) LedgerEntry::query()->where('source_type', 'commission')->sum('amount'),
            'commissions_pending' => (float) LedgerEntry::query()->where('source_type', 'commission')->where('status', 'pending')->sum('amount'),
            'commissions_available' => (float) LedgerEntry::query()->where('source_type', 'commission')->where('status', 'available')->sum('amount'),

            'withdrawals_requested' => Withdrawal::query()->count(),
            'withdrawals_processing' => Withdrawal::query()->where('status', 'processing')->count(),
            'withdrawals_paid' => Withdrawal::query()->where('status', 'paid')->count(),
            'withdrawals_failed' => Withdrawal::query()->where('status', 'failed')->count(),
        ];

        $pendingWithdrawals = Withdrawal::query()
            ->where('status', 'processing')
            ->with('user:id,first_name,last_name,affiliate_id')
            ->latest('requested_at')
            ->limit(6)
            ->get();

        $recentAffiliates = User::query()
            ->whereNotNull('affiliate_id')
            ->latest('affiliate_activated_at')
            ->limit(6)
            ->get(['id', 'first_name', 'last_name', 'email', 'affiliate_id', 'status', 'affiliate_activated_at']);

        $recentAudit = AuditLog::query()->with('admin:id,first_name,last_name')->latest('created_at')->limit(8)->get();

        $m = fn ($n) => number_format((float) $n, 0, ',', ' ');

        return Inertia::render('Admin/Dashboard', [
            'heading' => 'Tableau de bord',
            'eyebrow' => 'MALKUTHAR',
            'labels' => [
                'affiliates' => 'Affiliés',
                'revenue' => "Chiffre d'affaires",
            ],
            'formatted_stats' => [
                'revenue' => $m($stats['revenue']),
                'commissions_pending' => $m($stats['commissions_pending']),
                'commissions_available' => $m($stats['commissions_available']),
                'commissions_generated' => $m($stats['commissions_generated']),
                'cod_to_collect_amount' => $m($stats['cod_to_collect_amount']),
                'affiliates_total' => $m($stats['affiliates_total']),
                'affiliates_active' => $m($stats['affiliates_active']),
                'affiliates_new' => $m($stats['affiliates_new']),
                'affiliates_suspended' => $m($stats['affiliates_suspended']),
                'orders_paid' => $m($stats['orders_paid']),
                'withdrawals_requested' => $m($stats['withdrawals_requested']),
                'withdrawals_processing' => $m($stats['withdrawals_processing']),
                'withdrawals_paid' => $m($stats['withdrawals_paid']),
                'withdrawals_failed' => $m($stats['withdrawals_failed']),
            ],
            'stats' => $stats,
            'pendingWithdrawals' => $pendingWithdrawals,
            'recentAffiliates' => $recentAffiliates,
            'recentAudit' => $recentAudit,
        ]);
    }
}
