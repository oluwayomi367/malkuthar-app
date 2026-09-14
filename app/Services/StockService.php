<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * StockService — gestion du stock des produits physiques (§4, §10-§12).
 *
 * Modèle : `stock_available` = vendable immédiatement, `stock_reserved` =
 * retenu pour des commandes non encore payées, `stock_sold` = quantité
 * vendue nette (incrémentée à la vente, décrémentée au retour). Chaque
 * mouvement est journalisé (§11).
 *
 * Le « détenteur de stock » d'une ligne est sa VARIANTE si elle en a une
 * (§12), sinon le produit lui-même. Les deux modèles portent les mêmes
 * colonnes de stock.
 *
 * Seules les lignes de type PHYSICAL sont concernées ; toute autre ligne
 * est ignorée sans effet (digital/service n'ont pas de stock).
 */
class StockService
{
    /**
     * @throws InsufficientStockException si le stock disponible est
     *                                    inférieur à la quantité demandée.
     */
    public function reserve(OrderItem $item): void
    {
        if ($item->product_type !== 'physical') {
            return;
        }

        $holder = $this->lockHolder($item);
        if (! $holder) {
            return;
        }

        $available = (int) $holder->stock_available;

        if ($available < $item->quantity) {
            throw InsufficientStockException::forProduct($this->holderName($holder, $item), $item->quantity, $available);
        }

        $holder->decrement('stock_available', $item->quantity);
        $holder->increment('stock_reserved', $item->quantity);

        $this->log($holder, 'reserved', $item->quantity, $item);
    }

    /**
     * Paiement confirmé : la réservation devient une vente définitive.
     */
    public function markSold(OrderItem $item): void
    {
        if ($item->product_type !== 'physical') {
            return;
        }

        $holder = $this->lockHolder($item);
        if (! $holder) {
            return;
        }

        $holder->decrement('stock_reserved', min($item->quantity, (int) $holder->stock_reserved));
        $holder->increment('stock_sold', $item->quantity);

        $this->log($holder, 'sold', $item->quantity, $item);
    }

    /**
     * Commande annulée avant paiement : la réservation est libérée
     * (aucun impact sur `stock_sold`, la vente n'a jamais eu lieu).
     */
    public function releaseReservation(OrderItem $item): void
    {
        $this->restore($item, 'released', touchesSold: false);
    }

    /**
     * §80 — Remboursement d'une commande déjà payée : remise en stock et
     * la vente est décomptée.
     */
    public function restockFromReturn(OrderItem $item): void
    {
        $this->restore($item, 'returned', touchesSold: true);
    }

    /**
     * §11 — Ajustement manuel du stock disponible par un administrateur
     * (réapprovisionnement ou correction d'inventaire) sur un produit OU
     * une variante. Fixe la valeur absolue de `stock_available` et
     * journalise le delta ; on ne réécrit jamais une ligne d'historique.
     *
     * Retourne null si la valeur demandée est identique à l'actuelle.
     */
    public function adjustAvailable(
        Product|ProductVariant $holder,
        int $newAvailable,
        ?User $admin = null,
        ?string $reason = null,
    ): ?StockMovement {
        $newAvailable = max(0, $newAvailable);

        return DB::transaction(function () use ($holder, $newAvailable, $admin, $reason) {
            $locked = $this->lock($holder);
            if (! $locked) {
                return null;
            }

            $current = (int) $locked->stock_available;
            $delta = $newAvailable - $current;

            if ($delta === 0) {
                return null;
            }

            $locked->update(['stock_available' => $newAvailable]);

            return $this->log(
                $locked,
                $delta > 0 ? 'restocked' : 'adjustment',
                abs($delta),
                null,
                $admin,
                $reason,
            );
        });
    }

    private function restore(OrderItem $item, string $type, bool $touchesSold): void
    {
        if ($item->product_type !== 'physical') {
            return;
        }

        $holder = $this->lockHolder($item);
        if (! $holder) {
            return;
        }

        $holder->increment('stock_available', $item->quantity);
        $holder->decrement('stock_reserved', min($item->quantity, (int) $holder->stock_reserved));

        if ($touchesSold) {
            $holder->decrement('stock_sold', min($item->quantity, (int) $holder->stock_sold));
        }

        $this->log($holder, $type, $item->quantity, $item);
    }

    /**
     * Verrouille le détenteur de stock de la ligne : la variante si la
     * ligne en référence une (§12), sinon le produit.
     */
    private function lockHolder(OrderItem $item): Product|ProductVariant|null
    {
        if ($item->product_variant_id) {
            return ProductVariant::query()->whereKey($item->product_variant_id)->lockForUpdate()->first();
        }

        return Product::query()->whereKey($item->product_id)->lockForUpdate()->first();
    }

    private function lock(Product|ProductVariant $holder): Product|ProductVariant|null
    {
        return $holder->newQuery()->whereKey($holder->getKey())->lockForUpdate()->first();
    }

    private function holderName(Product|ProductVariant $holder, OrderItem $item): string
    {
        if ($holder instanceof ProductVariant) {
            return $item->product_name.' — '.$holder->name;
        }

        return $holder->name;
    }

    private function log(
        Product|ProductVariant $holder,
        string $type,
        int $quantity,
        ?OrderItem $item = null,
        ?User $admin = null,
        ?string $reason = null,
    ): StockMovement {
        $isVariant = $holder instanceof ProductVariant;

        return StockMovement::create([
            'product_id' => $isVariant ? $holder->product_id : $holder->id,
            'product_variant_id' => $isVariant ? $holder->id : null,
            'order_id' => $item?->order_id,
            'order_item_id' => $item?->id,
            'admin_id' => $admin?->getKey(),
            'type' => $type,
            'quantity' => $quantity,
            'variant_sku' => $isVariant ? $holder->sku : ($item?->variant_sku ?? ($holder instanceof Product ? $holder->sku : null)),
            'reason' => $reason,
        ]);
    }
}
