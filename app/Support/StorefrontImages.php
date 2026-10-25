<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Images de la vitrine publique, remplaçables depuis l'administration.
 *
 * Chaque emplacement a une image d'origine dans public/images ; une image
 * envoyée par l'admin est stockée dans public/uploads/vitrine et son chemin
 * est enregistré dans `Setting` sous « storefront.image.<clé> ».
 */
class StorefrontImages
{
    public const UPLOAD_PREFIX = '/uploads/vitrine/';

    /** clé => [page, libellé, emplacement, image d'origine] */
    public const SLOTS = [
        'brand.logo' => ['Général', 'Logo', "En-tête, pied de page et menus du site", '/images/logo.png'],

        'home.hero' => ['Accueil', "Grande bannière d'ouverture", 'Premier bloc de la page', '/images/hero-craft.jpg'],
        'home.tile_affiliation' => ['Accueil', 'Tuile « Programme d\'affiliation »', 'À droite de la grande bannière', '/images/affiliation-network.jpg'],
        'home.tile_promo' => ['Accueil', 'Tuile « Promotions en cours »', 'Remplace la tuile affiliation quand des promotions sont actives', '/images/catalog-banner.jpg'],
        'home.tile_vendor' => ['Accueil', 'Tuile « Créateurs et praticiens »', 'À droite de la grande bannière', '/images/about-maison.jpg'],
        'home.pillar_digital' => ['Accueil', 'Produits digitaux', 'Section « Trois façons de vous accompagner »', '/images/hero-digital.jpg'],
        'home.pillar_service' => ['Accueil', 'Services', 'Section « Trois façons de vous accompagner »', '/images/categories/service.jpg'],
        'home.pillar_physical' => ['Accueil', 'Produits physiques', 'Section « Trois façons de vous accompagner »', '/images/categories/physical.jpg'],
        'home.univers_digital' => ['Accueil', 'Savoirs & Formations', 'Section « Acheter par univers »', '/images/categories/digital.jpg'],
        'home.univers_physical' => ['Accueil', "Mode & Artisanat d'art", 'Section « Acheter par univers »', '/images/categories/physical.jpg'],
        'home.univers_residence' => ['Accueil', 'Résidences & Guest houses', 'Section « Acheter par univers »', '/images/categories/residence.jpg'],
        'home.univers_service' => ['Accueil', 'Soins & Praticiens', 'Section « Acheter par univers »', '/images/categories/service.jpg'],

        'shop.banner' => ['Boutique', 'Bandeau de la boutique', 'En haut de la page Boutique', '/images/catalog-banner.jpg'],

        'marketplace.banner' => ['Place de marché', 'Bandeau de la place de marché', 'En haut de la liste des offres', '/images/hero-craft.jpg'],
        'marketplace.landing_mode' => ['Place de marché', 'Mode, beauté & artisanat', 'Page de présentation de la place de marché', '/images/categories/physical.jpg'],
        'marketplace.landing_residence' => ['Place de marché', 'Résidences & guest houses', 'Page de présentation de la place de marché', '/images/categories/residence.jpg'],
        'marketplace.landing_service' => ['Place de marché', 'Soins & praticiens', 'Page de présentation de la place de marché', '/images/categories/service.jpg'],
        'marketplace.landing_digital' => ['Place de marché', 'Savoirs & formations', 'Page de présentation de la place de marché', '/images/categories/digital.jpg'],

        'about.hero' => ['À propos', 'Image principale', 'En haut de la page À propos', '/images/about-maison.jpg'],
        'about.digital' => ['À propos', 'Produits digitaux', 'Section des métiers', '/images/categories/digital.jpg'],
        'about.physical' => ['À propos', 'Objets et créations', 'Section des métiers', '/images/categories/physical.jpg'],
        'about.service' => ['À propos', 'Services et consultations', 'Section des métiers', '/images/categories/service.jpg'],

        'affiliation.hero' => ['Affiliation', 'Image principale', 'En haut de la page Affiliation', '/images/affiliation-hero.jpg'],
        'affiliation.network' => ['Affiliation', 'Réseau d\'affiliés', 'Section sur les trois niveaux', '/images/affiliation-network.jpg'],

        'contact.hero' => ['Contact', 'Image principale', 'En haut de la page Contact', '/images/contact-concierge.jpg'],

        'auth.login' => ['Connexion & inscription', 'Connexion', 'Grande photo à droite du formulaire', '/images/about-maison.jpg'],
        'auth.register' => ['Connexion & inscription', 'Créer un compte client', 'Grande photo à droite du formulaire', '/images/hero-digital.jpg'],
        'auth.register_affiliate' => ['Connexion & inscription', 'Devenir affilié', 'Grande photo à droite du formulaire', '/images/affiliation-network.jpg'],
        'auth.register_vendor' => ['Connexion & inscription', 'Créer un compte vendeur', 'Grande photo à droite du formulaire', '/images/hero-craft.jpg'],
        'auth.vendor_application' => ['Connexion & inscription', 'Ouvrir un espace vendeur', 'Grande photo à droite du formulaire', '/images/hero-craft.jpg'],
    ];

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::SLOTS);
    }

    /** Chemin public de l'image envoyée par l'admin, ou null si l'image d'origine est utilisée. */
    public static function custom(string $key): ?string
    {
        $value = Setting::get(self::settingKey($key));

        return filled($value) ? (string) $value : null;
    }

    /** URL de l'image à afficher pour cet emplacement. */
    public static function url(string $key): string
    {
        $path = self::custom($key) ?? (self::SLOTS[$key][3] ?? '/images/logo.png');

        return asset(ltrim($path, '/'));
    }

    /** clé => URL, partagé avec toutes les pages React de la vitrine. */
    public static function all(): array
    {
        $urls = [];
        foreach (array_keys(self::SLOTS) as $key) {
            $urls[$key] = self::url($key);
        }

        return $urls;
    }

    /** Emplacements regroupés par page, pour l'écran d'administration. */
    public static function grouped(): array
    {
        $groups = [];
        foreach (self::SLOTS as $key => [$page, $label, $where, $default]) {
            $groups[$page][] = [
                'key' => $key,
                'label' => $label,
                'where' => $where,
                'url' => self::url($key),
                'defaultUrl' => asset(ltrim($default, '/')),
                'custom' => self::custom($key) !== null,
            ];
        }

        return collect($groups)->map(fn ($slots, $page) => ['page' => $page, 'slots' => $slots])->values()->all();
    }

    public static function set(string $key, ?string $path): void
    {
        Setting::put(self::settingKey($key), $path ?? '');
    }

    /** Supprime du disque une image envoyée par l'admin (jamais une image d'origine). */
    public static function deleteFile(?string $path): void
    {
        if (! $path || ! Str::startsWith($path, self::UPLOAD_PREFIX)) {
            return;
        }

        $full = public_path(ltrim($path, '/'));
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private static function settingKey(string $key): string
    {
        return 'storefront.image.'.$key;
    }
}
