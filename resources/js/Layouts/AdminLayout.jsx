import React from 'react';
import { Link, usePage, router, Head } from '@inertiajs/react';

const NAV_GROUPS = [
    {
        title: 'Boutique & Exploitation',
        links: [
            {
                label: 'Tableau de bord',
                route: 'admin.dashboard',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M3 10h6V3H3zM11 21h6v-7h-6zM11 10h6V3h-6zM3 21h6v-7H3z" />
                    </svg>
                ),
            },
            {
                label: 'Produits',
                route: 'admin.products.index',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7M12 11v10" />
                    </svg>
                ),
            },
            {
                label: 'Commandes',
                route: 'admin.orders.index',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M6 2l1.5 3h9L18 2M3 6h18l-1.5 12.5a2 2 0 0 1-2 1.5H6.5a2 2 0 0 1-2-1.5zM9 11h6" />
                    </svg>
                ),
            },
            {
                label: 'Calendrier',
                route: 'admin.calendar.index',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M4 5h16v16H4zM4 9h16M8 3v4M16 3v4M9 14h2M13 14h2M9 17h2" />
                    </svg>
                ),
            },
            {
                label: 'Affiliés',
                route: 'admin.affiliates.index',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM3 20c0-3 2.7-5 6-5s6 2 6 5M16 3.5a3 3 0 0 1 0 6M21 20c0-2.5-1.8-4.4-4-4.9" />
                    </svg>
                ),
            },
        ],
    },
    {
        title: 'Place de marché',
        links: [
            {
                label: 'Réglages Vitrine & MP',
                route: 'admin.marketplace.settings.index',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19 12a7 7 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7 7 0 0 0-2.1-1.2L14 3h-4l-.4 2.6a7 7 0 0 0-2.1 1.2l-2.4-1-2 3.4 2 1.6a7 7 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 2.1 1.2L10 21h4l.4-2.6a7 7 0 0 0 2.1-1.2l2.4 1 2-3.4-2-1.6c.06-.4.1-.8.1-1.2z" />
                    </svg>
                ),
            },
            {
                label: 'Vendeurs',
                route: 'admin.marketplace.vendors.index',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M3 9l1-5h16l1 5M4 9h16v11H4zM9 20v-6h6v6" />
                    </svg>
                ),
            },
            {
                label: 'Catégories MP',
                route: 'admin.marketplace.categories.index',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M4 6h16M4 12h16M4 18h10" />
                    </svg>
                ),
            },
            {
                label: 'Offres MP',
                route: 'admin.marketplace.offers.index',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7" />
                    </svg>
                ),
            },
            {
                label: 'Commandes MP',
                route: 'admin.marketplace.orders.index',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M6 2l1.5 3h9L18 2M3 6h18l-1.5 12.5a2 2 0 0 1-2 1.5H6.5a2 2 0 0 1-2-1.5z" />
                    </svg>
                ),
            },
            {
                label: 'Versements MP',
                route: 'admin.marketplace.payouts.index',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M12 3v12m0 0l-4-4m4 4l4-4M4 21h16" />
                    </svg>
                ),
            },
        ],
    },
    {
        title: 'Finances & Contrôle',
        links: [
            {
                label: 'Retraits',
                route: 'admin.withdrawals.index',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M12 3v12m0 0l-4-4m4 4l4-4M4 21h16" />
                    </svg>
                ),
            },
            {
                label: 'Taux de commission',
                route: 'admin.commission-campaigns.index',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M12 3v18M17 6.5C17 4.6 14.8 3.5 12 3.5S7 4.6 7 6.5s2 2.7 5 3.2 5 1.6 5 3.6-2.2 3.2-5 3.2-5-1.1-5-3" />
                    </svg>
                ),
            },
            {
                label: 'Coupons',
                route: 'admin.coupons.index',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2 2 2 0 0 0 0 4 2 2 0 0 1-2 2H5a2 2 0 0 1-2-2 2 2 0 0 0 0-4zM9 8v8" />
                    </svg>
                ),
            },
            {
                label: "Journal d'audit",
                route: 'admin.audit-logs.index',
                icon: (
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M5 4h14v16l-4-2-3 2-3-2-4 2zM9 9h6M9 13h6" />
                    </svg>
                ),
            },
        ],
    },
];

export default function AdminLayout({ title, eyebrow = 'MALKUTHAR', heading, actions, children }) {
    const { auth, flash, url } = usePage().props;

    const handleLogout = (e) => {
        e.preventDefault();
        router.post(route('logout'));
    };

    const isCurrent = (routeName) => {
        try {
            if (typeof route !== 'undefined') {
                return route().current(routeName) || route().current(routeName + '.*');
            }
        } catch {
            // fallback
        }
        return false;
    };

    return (
        <div className="min-h-screen bg-[#f3f4f6] text-zinc-900 font-sans antialiased selection:bg-accent/20">
            <Head title={title ? `${title} — Administration MALKUTHAR` : 'Administration — MALKUTHAR'} />

            {/* TOP BAR FIXE */}
            <header className="sticky top-0 z-40 border-b border-zinc-200/90 bg-white/95 backdrop-blur-md">
                <div className="mx-auto flex h-14 max-w-[1400px] items-center justify-between px-4 sm:px-6">
                    <div className="flex items-center gap-3">
                        <Link href={route('admin.dashboard')} className="flex items-center gap-2.5 transition-opacity hover:opacity-85">
                            <span className="font-display text-base font-bold tracking-[0.16em] text-zinc-900">
                                MALKUTHAR
                            </span>
                            <span className="rounded-md border border-accent/40 bg-accent/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-accent">
                                Console Admin
                            </span>
                        </Link>
                    </div>

                    <div className="flex items-center gap-4 text-xs sm:text-sm">
                        <Link
                            href="/"
                            className="hidden items-center gap-1.5 font-medium text-zinc-600 transition-colors hover:text-zinc-900 sm:inline-flex"
                        >
                            <span>Voir le site</span>
                            <span className="text-zinc-400">↗</span>
                        </Link>
                        {auth?.user && (
                            <span className="hidden font-mono text-xs text-zinc-500 md:inline">
                                {auth.user.email}
                            </span>
                        )}
                        <button
                            type="button"
                            onClick={handleLogout}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 font-medium text-zinc-700 shadow-2xs transition-colors hover:bg-zinc-50 hover:text-zinc-900"
                        >
                            Quitter
                        </button>
                    </div>
                </div>
            </header>

            {/* NOTIFICATIONS FLASH */}
            {flash?.success && (
                <div className="mx-auto max-w-[1400px] px-4 pt-4 sm:px-6">
                    <div className="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 shadow-2xs">
                        <div className="flex items-center gap-2">
                            <span className="text-emerald-600 font-bold">✓</span>
                            <span>{flash.success}</span>
                        </div>
                    </div>
                </div>
            )}
            {flash?.error && (
                <div className="mx-auto max-w-[1400px] px-4 pt-4 sm:px-6">
                    <div className="flex items-center justify-between rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 shadow-2xs">
                        <div className="flex items-center gap-2">
                            <span className="text-rose-600 font-bold">⚠</span>
                            <span>{flash.error}</span>
                        </div>
                    </div>
                </div>
            )}

            {/* CORPS PRINCIPAL : SIDEBAR + CONTENU */}
            <div className="mx-auto max-w-[1400px] px-4 py-6 sm:px-6 lg:flex lg:gap-8 lg:py-8">
                {/* SIDEBAR DESKTOP */}
                <aside className="hidden w-64 shrink-0 lg:block">
                    <div className="sticky top-20 space-y-6">
                        <nav className="rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-xs">
                            {NAV_GROUPS.map((group, gIdx) => (
                                <div key={group.title} className={gIdx > 0 ? 'mt-5 pt-4 border-t border-zinc-100' : ''}>
                                    <p className="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-zinc-400">
                                        {group.title}
                                    </p>
                                    <div className="flex flex-col gap-0.5">
                                        {group.links.map((link) => {
                                            const active = isCurrent(link.route);
                                            return (
                                                <Link
                                                    key={link.route}
                                                    href={route(link.route)}
                                                    preserveScroll
                                                    className={[
                                                        'flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-all duration-150',
                                                        active
                                                            ? 'bg-zinc-900 text-white shadow-2xs font-semibold'
                                                            : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900',
                                                    ].join(' ')}
                                                >
                                                    <span className={active ? 'text-accent' : 'text-zinc-400'}>
                                                        {link.icon}
                                                    </span>
                                                    <span>{link.label}</span>
                                                </Link>
                                            );
                                        })}
                                    </div>
                                </div>
                            ))}
                        </nav>
                    </div>
                </aside>

                {/* NAVIGATION MOBILE CHIPS */}
                <nav className="mb-6 flex gap-1.5 overflow-x-auto pb-2 [scrollbar-width:none] lg:hidden [&::-webkit-scrollbar]:hidden">
                    {NAV_GROUPS.flatMap((g) => g.links).map((link) => {
                        const active = isCurrent(link.route);
                        return (
                            <Link
                                key={link.route}
                                href={route(link.route)}
                                preserveScroll
                                className={[
                                    'shrink-0 whitespace-nowrap rounded-full border px-3.5 py-1.5 text-xs font-medium transition-colors shadow-2xs',
                                    active
                                        ? 'border-zinc-900 bg-zinc-900 text-white font-semibold'
                                        : 'border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50',
                                ].join(' ')}
                            >
                                {link.label}
                            </Link>
                        );
                    })}
                </nav>

                {/* CONTENU DE LA PAGE */}
                <main className="min-w-0 flex-1 pb-16">
                    {/* EN-TÊTE DE SECTION */}
                    {(heading || eyebrow || actions) && (
                        <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                            <div>
                                {eyebrow && (
                                    <p className="text-[11px] font-bold uppercase tracking-[0.14em] text-accent">
                                        {eyebrow}
                                    </p>
                                )}
                                {heading && (
                                    <h1 className="mt-1 font-display text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">
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
