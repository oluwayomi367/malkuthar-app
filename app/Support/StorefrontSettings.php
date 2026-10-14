<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Paramètres d'exploitation de la vitrine publique : bandeau d'annonce défilant,
 * messages personnalisables par l'administrateur.
 */
class StorefrontSettings
{
    public const DEFAULT_TICKER = "Téléchargement immédiat des contenus digitaux · Livraison à domicile · Paiement Mobile Money ou carte · Programme d'affiliation sur trois niveaux";

    public static function announcementTicker(): string
    {
        $value = Setting::get('storefront.announcement_ticker');

        return filled($value) ? (string) $value : self::DEFAULT_TICKER;
    }

    public static function announcementTickerEnabled(): bool
    {
        $value = Setting::get('storefront.announcement_ticker_enabled');

        if ($value === null) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
