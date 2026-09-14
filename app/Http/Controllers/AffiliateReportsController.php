<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\AffiliateLineageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pages « Mes ventes » (§45), « Ma lignée » (§46) et « Mes gains » (§47)
 * de l'espace affilié. Les données personnelles des membres du réseau et
 * des clients ne sont jamais exposées (§46).
 */
class AffiliateReportsController extends Controller
{
    public function __construct(private readonly AffiliateLineageService $lineage) {}

    /**
     * §45 — Mes ventes : une ligne par commission (produit, date, niveau,
     * commission, statut), client anonymisé.
     */
    public function sales(Request $request): View
    {
        $affiliate = $this->affiliate($request);

        $sales = LedgerEntry::query()
            ->where('user_id', $affiliate->id)
            ->where('source_type', 'commission')
            ->with([
                'order:id,order_number,created_at,user_id',
                'orderItem:id,product_name,total_price,commission_base_amount',
            ])
            ->latest()
            ->paginate(25)
            ->through(fn (LedgerEntry $e) => [
                'reference' => $e->reference,
                'product' => $e->orderItem?->product_name ?? $e->description,
                'date' => $e->created_at,
                // §45 - montant de la vente (base de calcul de la commission).
                'amount' => (float) ($e->orderItem?->commission_base_amount ?? $e->orderItem?->total_price ?? 0),
                'level' => $e->level,
                'commission' => (float) $e->amount,
                'currency' => $e->currency,
                'status' => $e->status,
                'client' => $this->anonymizeClient($e->order?->user_id),
            ]);

        return view('affiliate.sales', ['affiliate' => $affiliate, 'sales' => $sales]);
    }

    /**
     * §46 — Ma lignée : agrégats par niveau (1 à 3).
     */
    public function lineage(Request $request): View
    {
        $affiliate = $this->affiliate($request);

        return view('affiliate.lineage', [
            'affiliate' => $affiliate,
            'levels' => $this->lineage->levels($affiliate),
        ]);
    }

    /**
     * §47 — Mes gains : historique financier complet, chaque opération
     * ayant une référence unique. Filtrable par période, niveau, produit,
     * statut et montant. §48 — le solde affiché est reconstitué depuis le
     * registre (ledger), jamais lu d'une simple variable.
     */
    public function earnings(Request $request): View
    {
        $affiliate = $this->affiliate($request);

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'level' => ['nullable', 'integer', 'between:1,3'],
            'product' => ['nullable', 'uuid'],
            'status' => ['nullable', 'string'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $entries = LedgerEntry::query()
            ->where('user_id', $affiliate->id)
            ->with('orderItem:id,product_name')
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($filters['level'] ?? null, fn ($q, $l) => $q->where('level', $l))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['min_amount'] ?? null, fn ($q, $m) => $q->where('amount', '>=', $m))
            ->when($filters['product'] ?? null, fn ($q, $pid) => $q->whereHas(
                'orderItem',
                fn ($oi) => $oi->where('product_id', $pid),
            ))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        // Produits sur lesquels l'affilié a réellement gagné, pour le filtre.
        $productIds = OrderItem::query()
            ->whereIn('id', LedgerEntry::query()
                ->where('user_id', $affiliate->id)
                ->whereNotNull('order_item_id')
                ->distinct()
                ->pluck('order_item_id'))
            ->distinct()
            ->pluck('product_id');

        $products = Product::query()->whereIn('id', $productIds)->orderBy('name')->get(['id', 'name']);

        $wallet = $affiliate->wallet;

        return view('affiliate.earnings', [
            'affiliate' => $affiliate,
            'entries' => $entries,
            'filters' => $filters,
            'products' => $products,
            // §48 — solde reconstitué à partir du registre.
            'ledgerBalance' => $wallet?->computeAvailableBalanceFromLedger() ?? 0.0,
            'totalEarned' => (float) LedgerEntry::query()->where('user_id', $affiliate->id)
                ->where('direction', 'credit')->where('source_type', 'commission')->sum('amount'),
            'totalWithdrawn' => (float) LedgerEntry::query()->where('user_id', $affiliate->id)
                ->where('direction', 'debit')->whereIn('status', ['processing', 'paid'])->sum('amount'),
            'currency' => $wallet?->currency ?? 'XOF',
        ]);
    }

    private function affiliate(Request $request): User
    {
        $user = $request->user();
        abort_unless($user?->isAffiliate(), 403, 'Cette page est réservée aux affiliés MALKUTHAR.');

        return $user;
    }

    private function anonymizeClient(?string $userId): string
    {
        if (! $userId) {
            return 'Client invité';
        }

        return 'Client #'.substr(md5($userId), 0, 6);
    }
}
