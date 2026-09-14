<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ProductSalesReport — agrège, pour un article donné, ses ventes réalisées
 * et les commissions d'affiliation qu'il a générées (§4/§5, §39).
 *
 *  - « Ventes » : lignes de commande rattachées à une commande PAYÉE.
 *  - « En attente » : lignes d'une commande « paiement à la livraison »
 *    pas encore encaissée (ni annulée).
 *  - « Commissions » : écritures de registre `commission` /
 *    `commission_reversal` pointant une ligne de ce produit. Seuls les
 *    produits digitaux affiliables en génèrent — sinon `applicable=false`
 *    et l'interface affiche « non affiliable » plutôt que des zéros.
 */
class ProductSalesReport
{
    /**
     * Rapport complet d'un seul produit (fiche admin).
     *
     * @return array<string, mixed>
     */
    public function forProduct(Product $product): array
    {
        $sales = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('oi.product_id', $product->id)
            ->whereNull('o.deleted_at')
            ->where('o.payment_status', 'paid')
            ->selectRaw('COALESCE(SUM(oi.quantity), 0) as units')
            ->selectRaw('COUNT(DISTINCT oi.order_id) as orders_count')
            ->selectRaw('COALESCE(SUM(oi.total_price - oi.discount_amount), 0) as revenue')
            ->first();

        $pending = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('oi.product_id', $product->id)
            ->whereNull('o.deleted_at')
            ->where('o.payment_status', 'pending')
            ->where('o.payment_provider', 'cod')
            ->where('o.status', '!=', 'cancelled')
            ->selectRaw('COALESCE(SUM(oi.quantity), 0) as units')
            ->selectRaw('COALESCE(SUM(oi.total_price - oi.discount_amount), 0) as revenue')
            ->first();

        $commissionRows = DB::table('ledger_entries as le')
            ->join('order_items as oi', 'oi.id', '=', 'le.order_item_id')
            ->where('oi.product_id', $product->id)
            ->whereIn('le.source_type', ['commission', 'commission_reversal'])
            ->groupBy('le.source_type', 'le.level', 'le.status')
            ->get(['le.source_type', 'le.level', 'le.status', DB::raw('SUM(le.amount) as total')]);

        $byLevel = [1 => 0.0, 2 => 0.0, 3 => 0.0];
        $byStatus = [];
        $commissionTotal = 0.0;
        $commissionReversed = 0.0;

        foreach ($commissionRows as $row) {
            $amount = (float) $row->total;

            if ($row->source_type === 'commission_reversal') {
                $commissionReversed += $amount;

                continue;
            }

            $commissionTotal += $amount;

            if ($row->level && isset($byLevel[(int) $row->level])) {
                $byLevel[(int) $row->level] += $amount;
            }

            $byStatus[$row->status] = ($byStatus[$row->status] ?? 0.0) + $amount;
        }

        return [
            'applicable' => $this->isApplicable($product),
            'currency' => $product->currency ?? 'XOF',
            'units_sold' => (int) $sales->units,
            'orders_count' => (int) $sales->orders_count,
            'revenue' => (float) $sales->revenue,
            'pending_units' => (int) $pending->units,
            'pending_revenue' => (float) $pending->revenue,
            'commission_total' => round($commissionTotal, 2),
            'commission_reversed' => round($commissionReversed, 2),
            'commission_net' => round($commissionTotal - $commissionReversed, 2),
            'commission_by_level' => $byLevel,
            'commission_by_status' => $byStatus,
        ];
    }

    /**
     * Rapport condensé pour une liste de produits (liste admin), sans N+1 :
     * deux requêtes groupées.
     *
     * @param  iterable<int|string>  $ids
     * @return Collection<string, array<string, mixed>>
     */
    public function forProductIds(iterable $ids): Collection
    {
        $ids = collect($ids)->filter()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $sales = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereIn('oi.product_id', $ids)
            ->whereNull('o.deleted_at')
            ->where('o.payment_status', 'paid')
            ->groupBy('oi.product_id')
            ->get([
                'oi.product_id',
                DB::raw('COALESCE(SUM(oi.quantity), 0) as units'),
                DB::raw('COALESCE(SUM(oi.total_price - oi.discount_amount), 0) as revenue'),
            ])
            ->keyBy('product_id');

        $commissions = DB::table('ledger_entries as le')
            ->join('order_items as oi', 'oi.id', '=', 'le.order_item_id')
            ->whereIn('oi.product_id', $ids)
            ->whereIn('le.source_type', ['commission', 'commission_reversal'])
            ->groupBy('oi.product_id')
            ->get([
                'oi.product_id',
                DB::raw("COALESCE(SUM(CASE WHEN le.source_type = 'commission' THEN le.amount ELSE -le.amount END), 0) as net"),
            ])
            ->keyBy('product_id');

        return $ids->mapWithKeys(function ($id) use ($sales, $commissions) {
            return [(string) $id => [
                'units_sold' => (int) ($sales[$id]->units ?? 0),
                'revenue' => (float) ($sales[$id]->revenue ?? 0),
                'commission_net' => round((float) ($commissions[$id]->net ?? 0), 2),
            ]];
        });
    }

    private function isApplicable(Product $product): bool
    {
        if ($product->is_affiliable) {
            return true;
        }

        return DB::table('order_items')
            ->where('product_id', $product->id)
            ->where('is_affiliable_snapshot', true)
            ->exists();
    }
}
