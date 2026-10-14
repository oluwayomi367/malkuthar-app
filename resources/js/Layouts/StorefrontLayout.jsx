import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import Alert from '../Components/ui/Alert';
import { cartCount } from '../lib/cart';

const DEFAULT_TICKER = "Téléchargement immédiat des produits numériques | Expédition suivie à domicile | Paiement Mobile Money et carte bancaire";

/** Retire les emojis et pictogrammes décoratifs saisis dans le bandeau. */
const stripPictographs = (text) => text
    .replace(/[\p{Extended_Pictographic}\u2726\u2727\uFE0F\u200D]/gu, '')
    .replace(/\s{2,}/g, ' ')
    .trim();

function Icon({ d, className = 'h-5 w-5', strokeWidth = 1.7 }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={strokeWidth}
             strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            {[].concat(d).map((p) => <path key={p} d={p} />)}
        </svg>
    );
}

const ICONS = {
    menu: 'M4 7h16M4 12h16M4 17h16',
    close: 'M6 6l12 12M18 6L6 18',
    chevron: 'M6 9l6 6 6-6',
    cart: ['M3 4h2.2l1.9 10.2a1.6 1.6 0 0 0 1.6 1.3h8.1a1.6 1.6 0 0 0 1.6-1.2L20 8H6.2', 'M9 20h.01', 'M17 20h.01'],
    user: ['M20 21v-1.5a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4V21', 'M12 11.5a4 4 0 1 0 0-8 4 4 0 0 0 0 8z'],
    arrow: 'M5 12h14M13 6l6 6-6 6',
    search: ['M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14z', 'M20 20l-3.5-3.5'],
    grid: 'M4 5h6v6H4zM14 5h6v6h-6zM4 15h6v6H4zM14 15h6v6h-6z',
};

/** Univers mis en avant dans la barre de catégories (mêmes destinations que l'accueil). */
const UNIVERS_NAV = [
    { label: 'Formations', href: '/boutique?type=digital', match: 'type=digital' },
    { label: 'Mode & Artisanat', href: '/boutique?type=physical', match: 'type=physical' },
    { label: 'Résidences', href: '/place-de-marche?pole=residences-guest-houses-immobilier', match: 'pole=residences' },
    { label: 'Soins & Praticiens', href: '/boutique?type=service', match: 'type=service' },
];

function SearchForm({ className = '', inputId }) {
    const { url } = usePage();
    const initial = (() => {
        try { return new URL(url, window.location.origin).searchParams.get('q') ?? ''; } catch { return ''; }
    })();
    const [q, setQ] = useState(initial);

    const submit = (e) => {
        e.preventDefault();
        router.get(route('catalog.index'), q.trim() ? { q: q.trim() } : {});
    };

    return (
        <form role="search" onSubmit={submit} className={['flex items-stretch', className].join(' ')}>
            <label htmlFor={inputId} className="sr-only">Rechercher un produit</label>
            <input id={inputId} type="search" value={q} onChange={(e) => setQ(e.target.value)}
                   placeholder="Rechercher un produit, un soin…"
                   className="min-h-11 w-full min-w-0 rounded-l-full border border-r-0 border-line-strong bg-surface px-5 text-[15px] text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none" />
            <button type="submit" aria-label="Lancer la recherche"
                    className="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-r-full bg-accent px-5 text-sm font-semibold text-white transition-colors hover:bg-accent-2">
                <Icon d={ICONS.search} className="h-[18px] w-[18px]" />
                <span className="hidden md:inline">Rechercher</span>
            </button>
        </form>
    );
}

function Brand({ onClick, size = 'md', tone = 'light' }) {
    return (
        <Link href="/" onClick={onClick} className="flex shrink-0 items-center gap-2.5" aria-label="MALKUTHAR, accueil">
            <img src="/images/logo.png" alt="" className={size === 'sm' ? 'h-8 w-8 object-contain' : 'h-9 w-9 object-contain'} />
            <span className={[
                'font-display font-semibold leading-none tracking-[0.14em]',
                tone === 'dark' ? 'text-white' : 'text-ink',
                size === 'sm' ? 'text-xl' : 'text-[22px]',
            ].join(' ')}>
                MALKUTHAR
            </span>
        </Link>
    );
}

function NavLink({ href, active, children }) {
    return (
        <Link
            href={href}
            aria-current={active ? 'page' : undefined}
            className={[
                'relative inline-flex min-h-11 items-center whitespace-nowrap px-3 text-sm font-medium transition-colors',
                active ? 'text-ink after:absolute after:inset-x-3 after:bottom-2 after:h-px after:bg-accent' : 'text-ink-2 hover:text-ink',
            ].join(' ')}
        >
            {children}
        </Link>
    );
}

function MegaMenu({ categories, open, setOpen }) {
    if (!open) return null;

    return (
        <div id="menu-boutique" onMouseEnter={() => setOpen(true)} onMouseLeave={() => setOpen(false)}
             className="absolute inset-x-0 top-full hidden border-b border-line bg-surface lg:block">
            <div className="mx-auto max-w-[1280px] px-6 py-8">
                <div className="grid grid-cols-3 gap-6">
                    {categories.map((c) => (
                        <Link key={c.type} href={route('catalog.index', { type: c.type })} onClick={() => setOpen(false)}
                              className="group flex gap-4 rounded-xl p-3 -m-3 transition-colors hover:bg-ground">
                            <svg className="mt-0.5 h-5 w-5 shrink-0 text-accent-2" viewBox="0 0 20 20" fill="none" stroke="currentColor"
                                 strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"
                                 dangerouslySetInnerHTML={{ __html: c.icon }} />
                            <span className="min-w-0">
                                <span className="block text-[15px] font-semibold text-ink">{c.label}</span>
                                <span className="mt-1 block text-sm leading-relaxed text-ink-2">{c.text}</span>
                            </span>
                        </Link>
                    ))}
                </div>
                <div className="mt-8 flex items-center justify-end border-t border-line pt-5">
                    <Link href={route('catalog.index')} onClick={() => setOpen(false)}
                          className="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-accent-2 hover:text-ink">
                        Voir toute la boutique
                        <Icon d={ICONS.arrow} className="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </div>
    );
}

function DrawerLink({ href, onClick, native = false, children }) {
    const cls = 'flex min-h-12 items-center border-b border-line px-1 text-[15px] font-medium text-ink';
    return native
        ? <a href={href} onClick={onClick} className={cls}>{children}</a>
        : <Link href={href} onClick={onClick} className={cls}>{children}</Link>;
}

function Drawer({ open, onClose, categories, marketplaceHref, user }) {
    const [shopOpen, setShopOpen] = useState(false);

    return (
        <div hidden={!open} className="fixed inset-0 z-50 lg:hidden">
            <div className="absolute inset-0 bg-ink/50" onClick={onClose} aria-hidden="true" />
            <div role="dialog" aria-modal="true" aria-label="Menu principal"
                 className="absolute inset-y-0 left-0 flex w-[88%] max-w-sm flex-col bg-surface">
                <div className="flex h-16 items-center justify-between border-b border-line px-5">
                    <Brand onClick={onClose} size="sm" />
                    <button type="button" onClick={onClose} aria-label="Fermer le menu"
                            className="-mr-2 inline-flex h-11 w-11 items-center justify-center rounded-full text-ink hover:bg-ground-alt">
                        <Icon d={ICONS.close} />
                    </button>
                </div>

                <nav className="flex-1 overflow-y-auto px-5 py-2" aria-label="Navigation principale">
                    <div className="border-b border-line">
                        <button type="button" onClick={() => setShopOpen((v) => !v)} aria-expanded={shopOpen}
                                className="flex min-h-12 w-full items-center justify-between px-1 text-left text-[15px] font-medium text-ink">
                            <span>Boutique</span>
                            <Icon d={ICONS.chevron} className={['h-4 w-4 text-ink-3 transition-transform', shopOpen ? 'rotate-180' : ''].join(' ')} />
                        </button>
                        {shopOpen && (
                            <div className="pb-3 pl-3">
                                <Link href={route('catalog.index')} onClick={onClose}
                                      className="flex min-h-11 items-center text-sm font-medium text-ink-2 hover:text-ink">
                                    Toute la boutique
                                </Link>
                                {categories.map((c) => (
                                    <Link key={c.type} href={route('catalog.index', { type: c.type })} onClick={onClose}
                                          className="flex min-h-11 items-center text-sm text-ink-2 hover:text-ink">
                                        {c.label}
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>
                    <DrawerLink href={marketplaceHref} onClick={onClose}>Place de marché</DrawerLink>
                    <DrawerLink href={route('affiliation')} onClick={onClose}>Affiliation</DrawerLink>
                    <DrawerLink href={route('about')} onClick={onClose}>À propos</DrawerLink>
                    <DrawerLink href={route('contact')} onClick={onClose}>Contact</DrawerLink>

                    <div className="mt-6 space-y-3">
                        {user ? (
                            <>
                                <a href={route('espace')}
                                   className="flex min-h-11 w-full items-center justify-center rounded-full bg-ink px-6 text-sm font-semibold text-white">
                                    Mon espace
                                </a>
                                <form method="POST" action={route('logout')}>
                                    <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.content} />
                                    <button className="flex min-h-11 w-full items-center justify-center rounded-full border border-line-strong px-6 text-sm font-medium text-ink-2 hover:text-ink">
                                        Se déconnecter
                                    </button>
                                </form>
                            </>
                        ) : (
                            <>
                                <a href={route('login')}
                                   className="flex min-h-11 w-full items-center justify-center rounded-full bg-ink px-6 text-sm font-semibold text-white">
                                    Connexion
                                </a>
                                <a href={route('register')}
                                   className="flex min-h-11 w-full items-center justify-center rounded-full border border-line-strong px-6 text-sm font-semibold text-ink">
                                    Créer un compte
                                </a>
                            </>
                        )}
                    </div>
                </nav>

                <div className="border-t border-line px-5 py-4 text-sm">
                    <a href={route('affiliate.program-terms')} className="text-ink-2 hover:text-ink">Conditions du programme</a>
                </div>
            </div>
        </div>
    );
}

function FooterColumn({ title, children }) {
    return (
        <div>
            <h2 className="font-sans text-sm font-semibold tracking-normal text-white">{title}</h2>
            <ul className="mt-4 space-y-3 text-sm text-night-ink-2">{children}</ul>
        </div>
    );
}

const footerLink = 'transition-colors hover:text-white';

function Footer({ marketplaceHref }) {
    return (
        <footer className="mt-24 bg-ink text-night-ink">
            <div className="mx-auto max-w-[1280px] px-4 py-16 sm:px-6">
                <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
                    <div>
                        <Link href="/" className="inline-flex items-center gap-2.5" aria-label="MALKUTHAR, accueil">
                            <span className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-surface p-1">
                                <img src="/images/logo.png" alt="" className="h-full w-full object-contain" />
                            </span>
                            <span className="font-display text-[22px] font-semibold leading-none tracking-[0.14em] text-white">MALKUTHAR</span>
                        </Link>
                        <p className="mt-4 max-w-xs text-sm leading-relaxed text-night-ink-2">
                            Formations, créations artisanales, résidences et consultations, réunies sur une même boutique.
                        </p>
                        <a href={route('contact')} className="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-accent-light hover:text-white">
                            Nous écrire
                        </a>
                    </div>
                    <FooterColumn title="Boutique">
                        <li><Link href="/boutique?type=digital" className={footerLink}>Produits digitaux</Link></li>
                        <li><Link href="/boutique?type=physical" className={footerLink}>Produits physiques</Link></li>
                        <li><Link href="/boutique?type=service" className={footerLink}>Services et consultations</Link></li>
                        <li><Link href={marketplaceHref} className={footerLink}>Place de marché</Link></li>
                    </FooterColumn>
                    <FooterColumn title="Programmes">
                        <li><Link href={route('affiliation')} className={footerLink}>L'affiliation</Link></li>
                        <li><a href={route('register.affiliate')} className={footerLink}>Devenir affilié</a></li>
                        <li><a href={route('register.vendor')} className={footerLink}>Devenir vendeur</a></li>
                        <li><Link href={route('marketplace')} className={footerLink}>Vendre sur la place de marché</Link></li>
                        <li><a href={route('affiliate.program-terms')} className={footerLink}>Conditions du programme</a></li>
                    </FooterColumn>
                    <FooterColumn title="Informations">
                        <li><Link href={route('about')} className={footerLink}>À propos</Link></li>
                        <li><Link href={route('contact')} className={footerLink}>Contact</Link></li>
                        <li><a href="/mentions-legales" className={footerLink}>Conditions générales de vente</a></li>
                        <li><a href="/confidentialite" className={footerLink}>Politique de confidentialité</a></li>
                        <li><a href="/cookies" className={footerLink}>Gestion des cookies</a></li>
                    </FooterColumn>
                </div>
                <div className="mt-14 flex flex-col gap-2 border-t border-night-line pt-6 text-sm text-night-ink-2 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {new Date().getFullYear()} MALKUTHAR SARL. Tous droits réservés.</p>
                    <p>Paiement sécurisé : FedaPay · CinetPay · Mobile Money</p>
                </div>
            </div>
        </footer>
    );
}

/** Layout persistant Inertia — navbar/drawer/footer ne se remontent pas
 * entre deux pages de la vitrine (voir chaque Page.jsx : `Page.layout = ...`). */
export default function StorefrontLayout({ children }) {
    const { props, url } = usePage();
    const {
        categories = [],
        marketplaceEnabled,
        auth,
        flash,
        announcementTicker = DEFAULT_TICKER,
        announcementTickerEnabled = true,
    } = props;

    const [drawerOpen, setDrawerOpen] = useState(false);
    const [megaOpen, setMegaOpen] = useState(false);
    const [count, setCount] = useState(0);
    const [bannerVisible, setBannerVisible] = useState(true);

    // Segments séparés par retour à la ligne, « | » ou « · » ; affichés sur une ligne, sans défilement.
    const tickerSegments = (announcementTicker || DEFAULT_TICKER)
        .split(/\r?\n|\||·/)
        .map((s) => stripPictographs(s))
        .filter(Boolean);

    useEffect(() => {
        const sync = () => setCount(cartCount());
        sync();
        window.addEventListener('mal-cart-changed', sync);
        window.addEventListener('storage', sync);
        return () => {
            window.removeEventListener('mal-cart-changed', sync);
            window.removeEventListener('storage', sync);
        };
    }, []);

    // Ferme menus et tiroir à chaque navigation, et sur Échap.
    useEffect(() => {
        setMegaOpen(false);
        setDrawerOpen(false);
    }, [url]);

    useEffect(() => {
        const onKey = (e) => {
            if (e.key === 'Escape') {
                setMegaOpen(false);
                setDrawerOpen(false);
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, []);

    const is = (prefix) => url === prefix || url.startsWith(prefix + '/') || url.startsWith(prefix + '?');
    const marketplaceHref = marketplaceEnabled ? route('marketplace.shop') : route('marketplace');
    const flashMessage = flash?.error
        ? { tone: 'error', text: flash.error }
        : flash?.success
            ? { tone: 'success', text: flash.success }
            : flash?.status
                ? { tone: 'info', text: flash.status }
                : null;

    return (
        <div className="min-h-screen bg-ground font-sans text-ink antialiased">
            <a href="#contenu" className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-full focus:bg-ink focus:px-5 focus:py-3 focus:text-sm focus:text-white">
                Aller au contenu
            </a>

            {/* Bandeau d'annonce (texte géré par l'administrateur) — fixe, une ligne. */}
            {bannerVisible && announcementTickerEnabled && tickerSegments.length > 0 && (
                <aside aria-label="Annonces" className="relative border-b border-line bg-ground-alt text-ink-2">
                    <div className="mx-auto flex min-h-10 max-w-[1280px] items-center justify-center px-12 text-center text-[13px]">
                        <p className="truncate sm:hidden">{tickerSegments[0]}</p>
                        <p className="hidden truncate sm:block">{tickerSegments.join(' · ')}</p>
                    </div>
                    <button type="button" onClick={() => setBannerVisible(false)} aria-label="Fermer le bandeau d'annonce"
                            className="absolute right-1 top-1/2 inline-flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full text-ink-3 hover:text-ink">
                        <Icon d={ICONS.close} className="h-4 w-4" />
                    </button>
                </aside>
            )}

            <header className="sticky top-0 z-40">
                {/* Barre principale : marque, recherche, compte, panier. */}
                <div className="bg-ink text-white">
                    <div className="mx-auto flex h-16 max-w-[1280px] items-center gap-3 px-4 sm:px-6 lg:h-[76px] lg:gap-8">
                        <button type="button" onClick={() => setDrawerOpen(true)} aria-label="Ouvrir le menu" aria-expanded={drawerOpen}
                                className="-ml-2 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-white hover:bg-white/10 lg:hidden">
                            <Icon d={ICONS.menu} />
                        </button>

                        <Brand tone="dark" />

                        <SearchForm inputId="recherche-entete" className="hidden flex-1 lg:flex lg:max-w-[620px]" />

                        <div className="ml-auto flex items-center gap-1 sm:gap-2">
                            <a href={auth?.user ? route('espace') : route('login')}
                               aria-label={auth?.user ? 'Mon espace' : 'Se connecter'}
                               className="inline-flex min-h-11 items-center gap-2.5 rounded-full px-2 text-sm text-white transition-colors hover:bg-white/10 sm:px-3">
                                <Icon d={ICONS.user} className="h-[22px] w-[22px]" />
                                <span className="hidden flex-col leading-tight sm:flex" aria-hidden="true">
                                    <span className="text-xs text-white/70">{auth?.user ? `Bonjour ${(auth.user.name || '').split(' ')[0]}` : 'Bienvenue'}</span>
                                    <span className="font-semibold">{auth?.user ? 'Mon espace' : 'Se connecter'}</span>
                                </span>
                            </a>

                            <Link href={route('cart')} aria-label={count > 0 ? `Panier, ${count} article${count > 1 ? 's' : ''}` : 'Panier'}
                                  className="relative -mr-2 inline-flex min-h-11 items-center gap-2.5 rounded-full px-2 text-sm text-white transition-colors hover:bg-white/10 sm:mr-0 sm:px-3">
                                <span className="relative">
                                    <Icon d={ICONS.cart} className="h-6 w-6" />
                                    {count > 0 && (
                                        <span aria-hidden="true" className="absolute -right-2 -top-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-accent-light px-1 text-xs font-bold text-ink">
                                            {count}
                                        </span>
                                    )}
                                </span>
                                <span className="hidden font-semibold sm:inline" aria-hidden="true">Panier</span>
                            </Link>
                        </div>
                    </div>

                    {/* Recherche pleine largeur sur mobile. */}
                    <div className="px-4 pb-3 sm:px-6 lg:hidden">
                        <SearchForm inputId="recherche-mobile" />
                    </div>
                </div>

                {/* Barre de catégories. */}
                <div className="relative hidden border-b border-line bg-surface lg:block">
                    <nav className="mx-auto flex h-12 max-w-[1280px] items-center gap-1 px-6" aria-label="Catégories">
                        <button type="button" onClick={() => setMegaOpen((v) => !v)} onMouseEnter={() => setMegaOpen(true)}
                                aria-expanded={megaOpen} aria-controls="menu-boutique"
                                className="mr-2 inline-flex min-h-10 shrink-0 items-center gap-2 whitespace-nowrap rounded-full bg-ground-alt px-4 text-sm font-semibold text-ink transition-colors hover:bg-line">
                            <Icon d={ICONS.grid} className="h-4 w-4" />
                            Toutes les catégories
                            <Icon d={ICONS.chevron} className={['h-4 w-4 text-ink-3 transition-transform', megaOpen ? 'rotate-180' : ''].join(' ')} />
                        </button>
                        {UNIVERS_NAV.map((u) => (
                            <NavLink key={u.href} href={u.href} active={url.includes(u.match)}>{u.label}</NavLink>
                        ))}
                        <span className="mx-2 h-5 w-px bg-line-strong" aria-hidden="true" />
                        <NavLink href={marketplaceHref} active={is('/place-de-marche') || is('/marketplace')}>Place de marché</NavLink>
                        <NavLink href={route('affiliation')} active={is('/affiliation') || is('/programme-affiliation')}>Affiliation</NavLink>
                        <a href={route('marketplace.become-vendor')}
                           className="ml-auto inline-flex min-h-11 items-center gap-1.5 whitespace-nowrap px-3 text-sm font-semibold text-space-vendor hover:text-ink">
                            Vendre sur MALKUTHAR
                            <Icon d={ICONS.arrow} className="h-4 w-4" />
                        </a>
                    </nav>
                    <MegaMenu categories={categories} open={megaOpen} setOpen={setMegaOpen} />
                </div>
            </header>

            <Drawer open={drawerOpen} onClose={() => setDrawerOpen(false)} categories={categories}
                    marketplaceHref={marketplaceHref} user={auth?.user} />

            {flashMessage && (
                <div className="mx-auto w-full max-w-2xl px-4 pt-6">
                    <Alert tone={flashMessage.tone}>{flashMessage.text}</Alert>
                </div>
            )}

            <main id="contenu">{children}</main>

            <Footer marketplaceHref={marketplaceHref} />

            {/* Contact WhatsApp — discret, icône seule. Remonte au-dessus de la barre d'action mobile (StickyCta). */}
            {props.whatsapp && (
            <a
                href={`https://wa.me/${props.whatsapp}`}
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Nous écrire sur WhatsApp"
                className="fixed bottom-5 right-5 z-40 inline-flex h-[52px] w-[52px] items-center justify-center rounded-full border border-line-strong bg-surface text-sage shadow-[0_1px_2px_rgba(28,25,23,0.06)] transition-colors hover:border-sage max-lg:[body:has([data-sticky-cta])_&]:bottom-24"
            >
                <svg className="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.06 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2.01-1.42.25-.7.25-1.29.17-1.42-.07-.12-.27-.2-.57-.35z" />
                    <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2zm0 18.15c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24 4.54 0 8.24 3.7 8.24 8.24 0 4.55-3.7 8.24-8.24 8.24z" />
                </svg>
            </a>
            )}
        </div>
    );
}
