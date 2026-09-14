<?php

namespace App\Services;

use App\Models\Coupon;

/**
 * CouponService — résolution et consommation des coupons (§79).
 */
class CouponService
{
    /**
     * Renvoie le coupon si le code correspond à un coupon actuellement
     * valide (actif, dans sa fenêtre de dates, sous son quota), sinon null.
     */
    public function resolve(?string $code): ?Coupon
    {
        if (! $code) {
            return null;
        }

        $coupon = Coupon::query()->where('code', $code)->first();

        return $coupon && $coupon->isCurrentlyValid() ? $coupon : null;
    }

    /**
     * Incrémente le compteur d'utilisation sous verrou (à appeler dans la
     * transaction du checkout). Renvoie false si le quota vient d'être
     * atteint entre-temps (le coupon ne doit alors pas être appliqué).
     */
    public function consume(Coupon $coupon): bool
    {
        $locked = Coupon::query()->whereKey($coupon->getKey())->lockForUpdate()->first();

        if (! $locked || ! $locked->isCurrentlyValid()) {
            return false;
        }

        $locked->increment('used_count');

        return true;
    }

    /**
     * §80 — relâche une utilisation (remboursement d'une commande).
     */
    public function release(Coupon $coupon): void
    {
        $locked = Coupon::query()->whereKey($coupon->getKey())->lockForUpdate()->first();

        if ($locked && $locked->used_count > 0) {
            $locked->decrement('used_count');
        }
    }
}
