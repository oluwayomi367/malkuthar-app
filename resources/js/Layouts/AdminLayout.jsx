import React, { useEffect, useState } from 'react';
import { Link, usePage, router, Head } from '@inertiajs/react';

/*
| Console d'administration — thème nuit.
| Fond `night`, surfaces `night-2`, filets `night-line`, texte `night-ink` /
| `night-ink-2`, accent `accent-light`. Les routes sont identiques à l'ancienne
| navigation ; seul le regroupement change.
*/

const Icon = ({ d }) => (
    <svg className="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d={d} />
    </svg>
);

const NAV_GROUPS = [
    {
        title: 'Pilotage',
        links: [
            { label: 'Tableau de bord', route: 'admin.dashboard', icon: 'M3 10h6V3H3zM11 21h6v-7h-6zM11 10h6V3h-6zM3 21h6v-7H3z' },
        ],
    },
    {
        title: 'Catalogue',
        links: [
            { label: 'Produits', route: 'admin.products.index', icon: 'M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7M12 11v10' },
            { label: 'Offres MP', route: 'admin.marketplace.offers.index', icon: 'M20 12l-8 8-9-9V3h8zM7.5 7.5h.01' },
            { label: 'Catégories MP', route: 'admin.marketplace.categories.index', icon: 'M4 6h16M4 12h16M4 18h10' },
        ],
    },
    {
        title: 'Ventes',
        links: [
            { label: 'Commandes', route: 'admin.orders.index', icon: 'M6 2l1.5 3h9L18 2M3 6h18l-1.5 12.5a2 2 0 0 1-2 1.5H6.5a2 2 0 0 1-2-1.5zM9 11h6' },
            { label: 'Commandes MP', route: 'admin.marketplace.orders.index', icon: 'M6 2l1.5 3h9L18 2M3 6h18l-1.5 12.5a2 2 0 0 1-2 1.5H6.5a2 2 0 0 1-2-1.5z' },
            { label: 'Calendrier', route: 'admin.calendar.index', icon: 'M4 5h16v16H4zM4 9h16M8 3v4M16 3v4M9 14h2M13 14h2M9 17h2' },
            { label: 'Coupons', route: 'admin.coupons.index', icon: 'M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2 2 2 0 0 0 0 4 2 2 0 0 1-2 2H5a2 2 0 0 1-2-2 2 2 0 0 0 0-4zM9 8v8' },
        ],
    },
    {
        title: 'Communauté',
        links: [
            { label: 'Affiliés', route: 'admin.affiliates.index', icon: 'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM3 20c0-3 2.7-5 6-5s6 2 6 5M16 3.5a3 3 0 0 1 0 6M21 20c0-2.5-1.8-4.4-4-4.9' },
            { label: 'Vendeurs', route: 'admin.marketplace.vendors.index', icon: 'M3 9l1-5h16l1 5M4 9h16v11H4zM9 20v-6h6v6' },
        ],
    },
    {
        title: 'Finances',
        links: [
            { label: 'Retraits', route: 'admin.withdrawals.index', icon: 'M12 3v12m0 0l-4-4m4 4l4-4M4 21h16' },
            { label: 'Versements MP', route: 'admin.marketplace.payouts.index', icon: 'M3 7h18v12H3zM3 11h18M7 15h3' },
            { label: 'Taux de commission', route: 'admin.commission-campaigns.index', icon: 'M19 5L5 19M7.5 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM16.5 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z' },
        ],
    },
    {
        title: 'Système',
        links: [
            { label: 'Réglages Vitrine & MP', route: 'admin.marketplace.settings.index', icon: 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19 12a7 7 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7 7 0 0 0-2.1-1.2L14 3h-4l-.4 2.6a7 7 0 0 0-2.1 1.2l-2.4-1-2 3.4 2 1.6a7 7 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 2.1 1.2L10 21h4l.4-2.6a7 7 0 0 0 2.1-1.2l2.4 1 2-3.4-2-1.6c.06-.4.1-.8.1-1.2z' },
            { label: "Journal d'audit", route: 'admin.audit-logs.index', icon: 'M5 4h14v16l-4-2-3 2-3-2-4 2zM9 9h6M9 13h6' },
        ],
    },
];

/** Vrai si la route courante appartient à la section du lien (index, show, edit…). */
const isCurrent = (routeName) => {
    try {
        if (typeof route === 'undefined') return false;
        const base = routeName.replace(/\.index$/, '');
        return route().current(routeName) || route().current(base + '.*');
    } catch {
        return false;
    }
};

function NavList({ onNavigate }) {
    return (
        <div className="space-y-6">
            {NAV_GROUPS.map((group) => (
                <div key={group.title}>
                    <p className="px-3 pb-2 text-xs font-semibold uppercase tracking-[0.1em] text-night-ink-2">
                        {group.title}
                    </p>
                    <ul className="space-y-0.5">
                        {group.links.map((link) => {
                            const active = isCurrent(link.route);
                            return (
                                <li key={link.route}>
                                    <Link
                                        href={route(link.route)}
                                        onClick={onNavigate}
                                        aria-current={active ? 'page' : undefined}
                                        className={[
                                            'relative flex min-h-10 items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                                            active
                                                ? 'bg-night-line font-semibold text-night-ink before:absolute before:inset-y-2 before:left-0 before:w-0.5 before:rounded-full before:bg-accent-light'
                                                : 'text-night-ink-2 hover:bg-night-line/50 hover:text-night-ink',
                                        ].join(' ')}
                                    >
                                        <span className={active ? 'text-accent-light' : ''}>
                                            <Icon d={link.icon} />
                                        </span>
                                        <span>{link.label}</span>
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            ))}
        </div>
    );
}

export default function AdminLayout({ title, eyebrow, heading, actions, children }) {
    const { auth, flash } = usePage().props;
    const [menuOpen, setMenuOpen] = useState(false);

    useEffect(() => {
        if (!menuOpen) return undefined;
        const onKey = (e) => e.key === 'Escape' && setMenuOpen(false);
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [menuOpen]);

    const handleLogout = (e) => {
        e.preventDefault();
        router.post(route('logout'));
    };

    // « MALKUTHAR » est déjà dans l'en-tête : inutile de le répéter en surtitre.
    const pageEyebrow = eyebrow && eyebrow !== 'MALKUTHAR' ? eyebrow : null;

    return (
        <div className="min-h-screen bg-night font-sans text-night-ink antialiased [color-scheme:dark] [&_:focus-visible]:outline-accent-light">
            <Head title={title ? `${title} — Administration MALKUTHAR` : 'Administration — MALKUTHAR'} />

            <header className="sticky top-0 z-40 border-b border-night-line bg-night">
                <div className="mx-auto flex h-16 max-w-[1280px] items-center justify-between gap-3 px-4 sm:px-6">
                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={() => setMenuOpen((v) => !v)}
                            aria-expanded={menuOpen}
                            aria-controls="admin-mobile-nav"
                            aria-label={menuOpen ? 'Fermer le menu' : 'Ouvrir le menu'}
                            className="-ml-2 inline-flex h-11 w-11 items-center justify-center rounded-lg text-night-ink-2 hover:bg-night-line/50 hover:text-night-ink lg:hidden"
                        >
                            {menuOpen ? (
                                <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" aria-hidden="true">
                                    <path d="M6 6l12 12M18 6L6 18" />
                                </svg>
                            ) : (
                                <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" aria-hidden="true">
                                    <path d="M4 7h16M4 12h16M4 17h16" />
                                </svg>
                            )}
                        </button>
                        <Link href={route('admin.dashboard')} className="flex items-center gap-3">
                            <span className="font-display text-2xl font-semibold tracking-[0.12em] text-night-ink">
                                MALKUTHAR
                            </span>
                            <span className="rounded-full border border-accent-light/40 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-[0.1em] text-accent-light">
                                Console
                            </span>
                        </Link>
                    </div>

                    <div className="flex items-center gap-2 text-sm sm:gap-4">
                        <a
                            href="/"
                            className="hidden min-h-11 items-center gap-1.5 font-medium text-night-ink-2 transition-colors hover:text-night-ink sm:inline-flex"
                        >
                            Voir le site
                            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                <path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" />
                            </svg>
                        </a>
                        {auth?.user && (
                            <span className="hidden max-w-[220px] truncate text-sm text-night-ink-2 md:inline" title={auth.user.email}>
                                {auth.user.email}
                            </span>
                        )}
                        <button
                            type="button"
                            onClick={handleLogout}
                            className="hidden min-h-11 items-center rounded-full border border-night-ink-2/60 px-4 sm:inline-flex text-sm font-semibold text-night-ink transition-colors hover:border-night-ink hover:bg-night-line/50"
                        >
                            Quitter
                        </button>
                    </div>
                </div>

                {/* Menu mobile repliable */}
                <nav
                    id="admin-mobile-nav"
                    aria-label="Navigation de la console"
                    hidden={!menuOpen}
                    className="max-h-[calc(100vh-4rem)] overflow-y-auto border-t border-night-line bg-night px-4 py-5 lg:hidden"
                >
                    <NavList onNavigate={() => setMenuOpen(false)} />
                    <div className="mt-6 flex flex-wrap items-center gap-3 border-t border-night-line px-3 pt-4 sm:hidden">
                        <a href="/" className="inline-flex min-h-11 items-center text-sm font-medium text-night-ink-2 hover:text-night-ink">
                            Voir le site
                        </a>
                        <button
                            type="button"
                            onClick={handleLogout}
                            className="ml-auto inline-flex min-h-11 items-center rounded-full border border-night-ink-2/60 px-4 text-sm font-semibold text-night-ink hover:bg-night-line/50"
                        >
                            Quitter
                        </button>
                    </div>
                </nav>
            </header>

            {(flash?.success || flash?.error) && (
                <div className="mx-auto max-w-[1280px] space-y-2 px-4 pt-5 sm:px-6">
                    {flash?.success && (
                        <div role="status" className="flex items-start gap-2.5 rounded-xl border border-sage bg-sage/20 px-4 py-3 text-sm text-sage-soft">
                            <svg className="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                <path d="M5 12.5l4.5 4.5L19 7.5" />
                            </svg>
                            <span>{flash.success}</span>
                        </div>
                    )}
                    {flash?.error && (
                        <div role="alert" className="flex items-start gap-2.5 rounded-xl border border-rust bg-rust/20 px-4 py-3 text-sm text-rust-tint">
                            <svg className="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                <path d="M12 8v5M12 16.5h.01M10.3 3.9L2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" />
                            </svg>
                            <span>{flash.error}</span>
                        </div>
                    )}
                </div>
            )}

            <div className="mx-auto max-w-[1280px] px-4 py-6 sm:px-6 lg:flex lg:gap-10 lg:py-8">
                <aside className="hidden w-60 shrink-0 lg:block">
                    <nav aria-label="Navigation de la console" className="sticky top-24 max-h-[calc(100vh-7rem)] overflow-y-auto pb-6">
                        <NavList />
                    </nav>
                </aside>

                <main className="min-w-0 flex-1 pb-16">
                    {(heading || actions) && (
                        <div className="mb-8 flex flex-wrap items-end justify-between gap-4 border-b border-night-line pb-6">
                            <div>
                                {pageEyebrow && (
                                    <p className="text-xs font-semibold uppercase tracking-[0.12em] text-accent-light">
                                        {pageEyebrow}
                                    </p>
                                )}
                                {heading && (
                                    <h1 className="mt-1 font-display text-3xl font-semibold text-night-ink sm:text-4xl">
                                        {heading}
                                    </h1>
                                )}
                            </div>
                            {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
                        </div>
                    )}

                    {children}
                </main>
            </div>
        </div>
    );
}
