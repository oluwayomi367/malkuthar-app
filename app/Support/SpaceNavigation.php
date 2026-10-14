<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Navigation des espaces connectés (client, affilié, vendeur).
 *
 * Source unique pour le sélecteur d'espace de l'en-tête, le menu latéral
 * et la barre d'onglets mobile : un même compte peut porter plusieurs
 * rôles, on doit pouvoir passer de l'un à l'autre sans se reconnecter.
 */
class SpaceNavigation
{
    public const SPACES = ['account', 'affiliate', 'vendor'];

    /** Espace courant déduit de l'URL. */
    public static function current(): string
    {
        $request = request();

        if ($request->is('vendeur*')) {
            return 'vendor';
        }

        if ($request->is('affiliate*')) {
            return 'affiliate';
        }

        return 'account';
    }

    public static function title(string $space): string
    {
        return match ($space) {
            'affiliate' => 'Espace affilié',
            'vendor' => 'Espace vendeur',
            default => 'Espace client',
        };
    }

    public static function shortLabel(string $space): string
    {
        return match ($space) {
            'affiliate' => 'Affilié',
            'vendor' => 'Vendeur',
            default => 'Client',
        };
    }

    /**
     * Espaces proposés dans le sélecteur pour cet utilisateur.
     *
     * @return list<array{space: string, label: string, href: string, enabled: bool}>
     */
    public static function switcher(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $items = [[
            'space' => 'account',
            'label' => 'Client',
            'href' => route('account.dashboard'),
            'enabled' => true,
        ]];

        $items[] = $user->isAffiliate()
            ? ['space' => 'affiliate', 'label' => 'Affilié', 'href' => route('affiliate.dashboard'), 'enabled' => true]
            : ['space' => 'affiliate', 'label' => 'Affilié', 'href' => route('affiliate.join'), 'enabled' => false];

        $vendor = $user->relationLoaded('vendorProfile') ? $user->vendorProfile : $user->vendorProfile()->first();

        if ($vendor && Route::has('vendor.dashboard')) {
            $items[] = [
                'space' => 'vendor',
                'label' => 'Vendeur',
                'href' => route($vendor->status === 'active' ? 'vendor.dashboard' : 'vendor.application.status'),
                'enabled' => true,
            ];
        }

        return $items;
    }

    /**
     * Rubriques d'un espace. `mobile` = présent dans la barre d'onglets.
     *
     * @return list<array{label: string, href: string, active: bool, icon: string, mobile: bool}>
     */
    public static function links(string $space): array
    {
        $links = match ($space) {
            'affiliate' => [
                ['label' => 'Tableau de bord', 'route' => 'affiliate.dashboard', 'icon' => 'grid', 'mobile' => true],
                ['label' => 'Mes ventes', 'route' => 'affiliate.sales', 'icon' => 'chart', 'mobile' => true],
                ['label' => 'Mes gains', 'route' => 'affiliate.earnings', 'icon' => 'coin', 'mobile' => true],
                ['label' => 'Mon réseau', 'route' => 'affiliate.lineage', 'icon' => 'network', 'mobile' => true],
                ['label' => 'Retraits', 'route' => 'affiliate.dashboard', 'anchor' => 'retrait', 'icon' => 'wallet', 'mobile' => false],
                ['label' => 'Conditions', 'route' => 'affiliate.program-terms', 'icon' => 'doc', 'mobile' => false],
            ],
            'vendor' => [
                ['label' => 'Tableau de bord', 'route' => 'vendor.dashboard', 'icon' => 'grid', 'mobile' => true],
                ['label' => 'Mes offres', 'route' => 'vendor.offers.index', 'also' => ['vendor.offers.*'], 'icon' => 'tag', 'mobile' => true],
                ['label' => 'Commandes', 'route' => 'vendor.orders.index', 'also' => ['vendor.orders.*'], 'icon' => 'bag', 'mobile' => true],
                ['label' => 'Revenus', 'route' => 'vendor.settlement.index', 'icon' => 'coin', 'mobile' => true],
                ['label' => 'Versements', 'route' => 'vendor.payouts.index', 'icon' => 'wallet', 'mobile' => false],
                ['label' => 'Ma boutique', 'route' => 'vendor.profile.edit', 'icon' => 'store', 'mobile' => false],
            ],
            default => [
                ['label' => 'Tableau de bord', 'route' => 'account.dashboard', 'icon' => 'grid', 'mobile' => true],
                ['label' => 'Bibliothèque', 'route' => 'account.downloads', 'icon' => 'book', 'mobile' => true],
                ['label' => 'Commandes', 'route' => 'account.orders', 'also' => ['account.orders.*'], 'icon' => 'bag', 'mobile' => true],
                ['label' => 'Rendez-vous', 'route' => 'account.appointments', 'icon' => 'calendar', 'mobile' => true],
                ['label' => 'Profil', 'route' => 'account.profile', 'icon' => 'user', 'mobile' => false],
            ],
        };

        return array_values(array_filter(array_map(function (array $link) {
            if (! Route::has($link['route'])) {
                return null;
            }

            $href = route($link['route']).(isset($link['anchor']) ? '#'.$link['anchor'] : '');
            $active = ! isset($link['anchor'])
                && (request()->routeIs($link['route']) || request()->routeIs(...($link['also'] ?? ['__none__'])));

            return [
                'label' => $link['label'],
                'href' => $href,
                'active' => $active,
                'icon' => self::ICONS[$link['icon']],
                'mobile' => $link['mobile'],
            ];
        }, $links)));
    }

    /** Tracés SVG (viewBox 24, trait). */
    public const ICONS = [
        'grid' => 'M3 10h6V3H3zM11 21h6v-7h-6zM11 10h6V3h-6zM3 21h6v-7H3z',
        'chart' => 'M4 18V9M10 18V5M16 18v-6M2 21h18',
        'coin' => 'M12 3v18M17 6.5C17 4.6 14.8 3.5 12 3.5S7 4.6 7 6.5s2 2.7 5 3.2 5 1.6 5 3.6-2.2 3.2-5 3.2-5-1.1-5-3',
        'network' => 'M12 3a3 3 0 1 1 0 6 3 3 0 0 1 0-6zM5 21a3 3 0 1 1 0-6 3 3 0 0 1 0 6zM19 21a3 3 0 1 1 0-6 3 3 0 0 1 0 6zM12 9v3M12 12l-7 3M12 12l7 3',
        'wallet' => 'M3 7h18v12H3zM3 11h18M16 15h2',
        'doc' => 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h6',
        'tag' => 'M20 12l-8 8-9-9V3h8zM7.5 7.5h.01',
        'bag' => 'M5 7h14l-1.2 12.2a1 1 0 0 1-1 .8H7.2a1 1 0 0 1-1-.8zM9 7a3 3 0 0 1 6 0',
        'store' => 'M3 9l1-5h16l1 5M4 9h16v11H4zM9 20v-6h6v6',
        'book' => 'M4 19V5a2 2 0 0 1 2-2h12v18H6a2 2 0 0 1-2-2zM8 7h6',
        'calendar' => 'M4 5h16v16H4zM4 9h16M8 3v4M16 3v4',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM5 20c0-3.3 3.1-6 7-6s7 2.7 7 6',
    ];
}
