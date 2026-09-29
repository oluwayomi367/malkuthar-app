import { useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import Alert from '../Components/ui/Alert';
import { cartCount } from '../lib/cart';

function NavLink({ href, active, children, badge }) {
    return (
        <Link
            href={href}
            className={[
                'inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs uppercase tracking-[0.06em] transition-all duration-150',
                active
                    ? 'bg-ground-alt/90 font-extrabold text-ink shadow-2xs ring-1 ring-line-strong/80'
                    : 'font-semibold text-ink-2 hover:bg-ground-alt/60 hover:text-ink',
            ].join(' ')}
        >
            <span>{children}</span>
            {badge && (
                <span className="rounded-md bg-accent/15 px-1.5 py-0.5 text-[9px] font-extrabold tracking-wider text-accent uppercase">
                    {badge}
                </span>
            )}
        </Link>
    );
}

function MegaMenu({ categories, open, setOpen }) {
    if (!open) return null;

    return (
        <div onMouseEnter={() => setOpen(true)} onMouseLeave={() => setOpen(false)}
             className="absolute inset-x-0 top-full hidden px-4 pt-2 sm:px-6 lg:block">
            <div className="mx-auto max-w-[1280px] overflow-hidden rounded-2xl border border-line-strong/70 bg-surface shadow-[0_20px_48px_-16px_rgba(46,44,40,0.18)]">
                <div className="grid grid-cols-3 gap-3 p-5">
                    {categories.map((c) => (
                        <Link key={c.type} href={route('catalog.index', { type: c.type })} onClick={() => setOpen(false)}
                              className="group flex gap-3.5 rounded-xl border border-line/60 bg-ground/40 p-4 transition-all duration-200 hover:border-accent/60 hover:bg-surface hover:shadow-xs">
                            <span className="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-line bg-white text-accent shadow-2xs group-hover:border-accent group-hover:bg-accent/10">
                                <svg className="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round"
                                     dangerouslySetInnerHTML={{ __html: c.icon }} />
                            </span>
                            <span className="min-w-0">
                                <span className="block font-display text-[15px] font-bold text-ink group-hover:text-accent transition-colors">{c.label}</span>
                                <span className="mt-1 block text-xs leading-relaxed text-ink-2">{c.text}</span>
                            </span>
                        </Link>
                    ))}
                </div>
                <div className="flex items-center justify-between border-t border-line bg-ground-alt/70 px-6 py-3.5 text-xs">
                    <span className="font-medium text-ink-3">Trois voies, une maison.</span>
                    <Link href={route('catalog.index')} onClick={() => setOpen(false)} className="inline-flex items-center gap-1.5 font-bold text-accent hover:text-accent-2 transition-colors">
                        <span>Explorer la boutique</span>
                        <span aria-hidden="true">→</span>
                    </Link>
                </div>
            </div>
        </div>
    );
}

function Drawer({ open, onClose, categories, marketplaceEnabled, user }) {
    const [prodOpen, setProdOpen] = useState(false);

    return (
        <div hidden={!open} className="fixed inset-0 z-50 lg:hidden">
            <div className="absolute inset-0 bg-black/60 backdrop-blur-sm" onClick={onClose} />
            <div className="absolute inset-y-0 left-0 flex w-[86%] max-w-sm flex-col border-r border-line-strong/80 bg-surface shadow-2xl">
                <div className="flex h-18 items-center justify-between border-b border-line px-5">
                    <Link href="/" onClick={onClose} className="flex items-center gap-3">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-line-strong/70 bg-white p-1 shadow-2xs">
                            <img src="/images/logo.png" alt="Logo MALKUTHAR" className="h-full w-full object-contain" />
                        </div>
                        <div className="flex flex-col">
                            <span className="font-display text-[15.5px] font-extrabold tracking-[0.16em] text-ink leading-tight">MALKUTHAR</span>
                            <span className="text-[9px] font-extrabold tracking-[0.22em] text-accent uppercase leading-none mt-0.5">Maison & Marché</span>
                        </div>
                    </Link>
                    <button type="button" onClick={onClose} aria-label="Fermer"
                            className="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-line bg-ground-alt/60 text-ink hover:bg-ground-alt">
                        <svg className="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M5 5l10 10M15 5L5 15" strokeLinecap="round" />
                        </svg>
                    </button>
                </div>

                <nav className="flex-1 space-y-1 overflow-y-auto p-4">
                    <Link href="/" onClick={onClose} className="flex items-center rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-ground-alt">
                        Accueil
                    </Link>

                    <div>
                        <button type="button" onClick={() => setProdOpen((v) => !v)}
                                className="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-ink hover:bg-ground-alt">
                            <span>Produits</span>
                            <svg className={['h-4 w-4 text-ink-3 transition-transform', prodOpen ? 'rotate-180 text-accent' : ''].join(' ')} viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M4 6l4 4 4-4" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                        </button>
                        {prodOpen && (
                            <div className="my-1 ml-3 space-y-1 border-l-2 border-accent/40 pl-3">
                                {categories.map((c) => (
                                    <Link key={c.type} href={route('catalog.index', { type: c.type })} onClick={onClose}
                                          className="block rounded-lg px-2.5 py-2 text-xs font-medium text-ink-2 hover:bg-ground-alt hover:text-ink">
                                        {c.label}
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>

                    <Link href={route('affiliation')} onClick={onClose} className="flex items-center rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-ground-alt">
                        Affiliation
                    </Link>
                    <Link href={marketplaceEnabled ? route('marketplace.shop') : route('marketplace')} onClick={onClose}
                          className="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-ground-alt">
                        <span>Marketplace</span>
                        <span className="rounded-md bg-accent/15 px-1.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-accent">Place</span>
                    </Link>
                    <Link href={route('about')} onClick={onClose} className="flex items-center rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-ground-alt">
                        À propos
                    </Link>
                    <Link href={route('contact')} onClick={onClose} className="flex items-center rounded-xl px-3 py-2.5 text-sm font-semibold text-ink hover:bg-ground-alt">
                        Contact
                    </Link>

                    <div className="my-3 border-t border-line" />
                    {user ? (
                        <div className="space-y-1">
                            <a href="/mon-compte" className="flex items-center rounded-xl px-3 py-2.5 text-sm font-bold text-ink hover:bg-ground-alt">
                                Mon compte
                            </a>
                            <form method="POST" action={route('logout')}>
                                <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.content} />
                                <button className="block w-full rounded-xl px-3 py-2.5 text-left text-sm font-medium text-rust hover:bg-rust/10">
                                    Se déconnecter
                                </button>
                            </form>
                        </div>
                    ) : (
                        <div className="space-y-2 pt-2">
                            <a href={route('login')} className="flex w-full items-center justify-center rounded-xl border border-line-strong/80 bg-white py-2.5 text-xs font-bold uppercase tracking-wider text-ink shadow-2xs hover:bg-ground-alt">
                                Connexion
                            </a>
                            <a href={route('register')} className="flex w-full items-center justify-center rounded-xl bg-ink py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-2xs hover:bg-ink/90">
                                Créer un compte
                            </a>
                        </div>
                    )}
                </nav>

                <div className="border-t border-line bg-ground-alt/40 px-5 py-3 text-xs text-ink-3">
                    <a href={route('affiliate.program-terms')} className="font-medium hover:text-ink">Conditions du programme</a>
                </div>
            </div>
        </div>
    );
}

function Footer() {
    return (
        <footer className="mt-24 border-t border-line-strong/70 bg-surface/90">
            <div className="mx-auto max-w-[1280px] px-4 py-14 sm:px-6">
                <div className="grid grid-cols-2 gap-8 sm:grid-cols-4">
                    <div className="col-span-2 sm:col-span-1">
                        <Link href="/" className="flex items-center gap-3">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-line-strong/60 bg-white p-1 shadow-2xs">
                                <img src="/images/logo.png" alt="Logo MALKUTHAR" className="h-full w-full object-contain" />
                            </div>
                            <div className="flex flex-col">
                                <span className="font-display text-[15px] font-extrabold tracking-[0.16em] text-ink leading-tight">MALKUTHAR</span>
                                <span className="text-[9px] font-extrabold tracking-[0.22em] text-accent uppercase leading-none mt-0.5">Maison & Marché</span>
                            </div>
                        </Link>
                        <p className="mt-3 max-w-xs text-xs leading-relaxed text-ink-2">Produits digitaux, créations d'exception et services spécialisés — une seule maison d'honneur.</p>
                        <ul className="mt-4 space-y-2 text-xs font-medium text-ink-2">
                            <li><Link href={route('about')} className="transition-colors hover:text-accent">À propos de la Maison</Link></li>
                            <li><Link href={route('marketplace')} className="transition-colors hover:text-accent">Vendre sur la Marketplace</Link></li>
                            <li><Link href={route('contact')} className="transition-colors hover:text-accent">Contact & Support</Link></li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-[10px] font-bold uppercase tracking-[0.15em] text-accent">La Boutique</p>
                        <ul className="mt-3.5 space-y-2.5 text-xs text-ink-2">
                            <li><Link href="/boutique?type=digital" className="transition-colors hover:text-ink">Produits digitaux</Link></li>
                            <li><Link href="/boutique?type=physical" className="transition-colors hover:text-ink">Produits physiques</Link></li>
                            <li><Link href="/boutique?type=service" className="transition-colors hover:text-ink">Services & Consultations</Link></li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-[10px] font-bold uppercase tracking-[0.15em] text-accent">Programmes</p>
                        <ul className="mt-3.5 space-y-2.5 text-xs text-ink-2">
                            <li><Link href={route('affiliation')} className="transition-colors hover:text-ink">Affiliation — Découvrir</Link></li>
                            <li><a href={route('register.affiliate')} className="transition-colors hover:text-ink">Devenir affilié officiel</a></li>
                            <li><a href={route('register.vendor')} className="transition-colors hover:text-ink">Devenir vendeur partenaire</a></li>
                            <li><a href={route('affiliate.program-terms')} className="transition-colors hover:text-ink">Conditions du programme</a></li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-[10px] font-bold uppercase tracking-[0.15em] text-accent">Informations</p>
                        <ul className="mt-3.5 space-y-2.5 text-xs text-ink-2">
                            <li><a href="/mentions-legales" className="transition-colors hover:text-ink">Conditions Générales de Vente</a></li>
                            <li><a href="/confidentialite" className="transition-colors hover:text-ink">Politique de confidentialité</a></li>
                            <li><a href="/cookies" className="transition-colors hover:text-ink">Gestion des cookies</a></li>
                        </ul>
                    </div>
                </div>
                <div className="mt-10 flex flex-col gap-2 border-t border-line pt-6 text-xs text-ink-3 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {new Date().getFullYear()} MALKUTHAR SARL. Tous droits réservés.</p>
                    <p className="font-medium text-ink-2">Paiement sécurisé crypté — FedaPay · CinetPay · Mobile Money</p>
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
        announcementTicker = "✨ Créations & savoirs d'exception pour tous vos projets · ⚡ Téléchargement immédiat 24/7 · 📦 Expédition soignée à domicile · 🤝 Affiliation 3 niveaux (jusqu'à 30 %) · 💳 Paiements sécurisés Mobile Money & Cartes",
        announcementTickerEnabled = true,
    } = props;

    const [drawerOpen, setDrawerOpen] = useState(false);
    const [megaOpen, setMegaOpen] = useState(false);
    const [count, setCount] = useState(0);
    const [bannerVisible, setBannerVisible] = useState(true);

    const tickerSegments = (announcementTicker || '')
        .split(/\r?\n|\|/)
        .map((s) => s.trim())
        .filter(Boolean);

    const activeSegments = tickerSegments.length > 0
        ? tickerSegments
        : ["✨ Créations & savoirs d'exception pour tous vos projets · ⚡ Téléchargement immédiat 24/7 · 📦 Expédition soignée à domicile · 🤝 Affiliation 3 niveaux (jusqu'à 30 %) · 💳 Paiements sécurisés Mobile Money & Cartes"];

    const repeatCount = Math.max(2, Math.ceil(6 / activeSegments.length));
    const trackItems = Array(repeatCount).fill(activeSegments).flat();

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

    const is = (prefix) => url === prefix || url.startsWith(prefix + '/') || url.startsWith(prefix + '?');
    const flashMessage = flash?.error
        ? { tone: 'error', text: flash.error }
        : flash?.success
            ? { tone: 'success', text: flash.success }
            : flash?.status
                ? { tone: 'info', text: flash.status }
                : null;

    return (
        <div className="min-h-screen bg-ground font-sans text-ink antialiased">
            <header className="sticky top-0 z-40 border-b border-line-strong/70 bg-surface/98 backdrop-blur-md shadow-[0_2px_12px_-4px_rgba(46,44,40,0.06)]">
                <div className="relative mx-auto flex h-18 max-w-[1280px] items-center gap-4 px-4 sm:px-6 lg:gap-8">
                    <button type="button" onClick={() => setDrawerOpen(true)} aria-label="Ouvrir le menu"
                            className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-line-strong/60 bg-white text-ink shadow-2xs transition-colors hover:bg-ground-alt lg:hidden">
                        <svg className="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.8">
                            <path d="M3 6h14M3 10h14M3 14h14" strokeLinecap="round" />
                        </svg>
                    </button>

                    <Link href="/" className="group flex shrink-0 items-center gap-3">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-line-strong/70 bg-white p-1 shadow-2xs transition-all duration-200 group-hover:scale-105 group-hover:border-accent group-hover:shadow-xs">
                            <img src="/images/logo.png" alt="Logo MALKUTHAR" className="h-full w-full object-contain" />
                        </div>
                        <div className="flex flex-col">
                            <span className="font-display text-[16px] font-extrabold tracking-[0.16em] text-ink leading-tight transition-colors group-hover:text-accent">MALKUTHAR</span>
                            <span className="text-[9px] font-extrabold tracking-[0.24em] text-accent uppercase leading-none mt-0.5">Maison & Marché</span>
                        </div>
                    </Link>

                    <nav className="hidden items-center gap-1.5 lg:flex">
                        <NavLink href="/" active={url === '/'}>Accueil</NavLink>

                        <button type="button" onClick={() => setMegaOpen((v) => !v)} onMouseEnter={() => setMegaOpen(true)}
                                aria-expanded={megaOpen}
                                className={[
                                    'inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs uppercase tracking-[0.06em] transition-all duration-150',
                                    megaOpen
                                        ? 'bg-ground-alt/90 font-extrabold text-ink shadow-2xs ring-1 ring-line-strong/80'
                                        : 'font-semibold text-ink-2 hover:bg-ground-alt/60 hover:text-ink',
                                ].join(' ')}>
                            <span>Produits</span>
                            <svg className={['h-3 w-3 transition-transform duration-200', megaOpen ? 'rotate-180 text-accent' : 'text-ink-3'].join(' ')} viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M4 6l4 4 4-4" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                        </button>

                        <NavLink href={route('affiliation')} active={is('/affiliation') || is('/programme-affiliation')}>Affiliation</NavLink>
                        <NavLink href={marketplaceEnabled ? route('marketplace.shop') : route('marketplace')} active={is('/place-de-marche') || is('/marketplace')} badge="Place">Marketplace</NavLink>
                        <NavLink href={route('about')} active={is('/a-propos')}>À propos</NavLink>
                        <NavLink href={route('contact')} active={is('/contact')}>Contact</NavLink>
                    </nav>

                    <div className="ml-auto flex items-center gap-2 sm:gap-2.5">
                        {auth?.user ? (
                            <a href="/mon-compte"
                               className="hidden sm:inline-flex items-center gap-2 rounded-xl border border-line-strong/70 bg-white px-3.5 py-2 text-xs font-bold text-ink shadow-2xs transition-all hover:border-accent hover:bg-ground-alt/40 hover:shadow-xs">
                                <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-accent/15 text-[10px] font-black text-accent uppercase">
                                    {auth.user.name ? auth.user.name.charAt(0) : 'U'}
                                </span>
                                <span className="max-w-[120px] truncate">{auth.user.name || 'Mon compte'}</span>
                            </a>
                        ) : (
                            <a href={route('login')}
                               className="hidden sm:inline-flex items-center gap-1.5 rounded-xl border border-line-strong/70 bg-white px-3.5 py-2 text-xs font-bold uppercase tracking-[0.06em] text-ink shadow-2xs transition-all hover:border-accent hover:bg-ground-alt/40 hover:text-accent hover:shadow-xs">
                                <svg className="h-3.5 w-3.5 text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                <span>Connexion</span>
                            </a>
                        )}

                        <Link href={route('cart')} aria-label="Panier"
                              className="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-line-strong/70 bg-white text-ink shadow-2xs transition-all hover:border-accent hover:bg-ground-alt/40 hover:text-accent hover:shadow-xs">
                            <svg className="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6">
                                <path d="M3 4h2l1.6 9.2a1.5 1.5 0 0 0 1.48 1.24h6.84a1.5 1.5 0 0 0 1.47-1.2L18.5 7H6" strokeLinecap="round" strokeLinejoin="round" />
                                <circle cx="8.5" cy="17" r="1" /><circle cx="15.5" cy="17" r="1" />
                            </svg>
                            {count > 0 && (
                                <span className="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rust px-1 text-[10px] font-extrabold text-white ring-2 ring-white shadow-xs">
                                    {count}
                                </span>
                            )}
                        </Link>
                    </div>

                    <MegaMenu categories={categories} open={megaOpen} setOpen={setMegaOpen} />
                </div>
            </header>

            {/* Bandeau d'annonce défilant sous le menu (mis à jour par l'administrateur) */}
            {bannerVisible && announcementTickerEnabled && (
                <aside
                    aria-label="Annonces défilantes"
                    className="relative z-30 overflow-hidden border-b border-black/10 bg-black text-white select-none shadow-xs"
                >
                    {/* Estompage dégradé subtil à gauche et à droite */}
                    <div className="pointer-events-none absolute inset-y-0 left-0 z-10 w-8 sm:w-16 bg-gradient-to-r from-black via-black/85 to-transparent" />
                    <div className="pointer-events-none absolute inset-y-0 right-0 z-10 w-12 sm:w-20 bg-gradient-to-l from-black via-black/85 to-transparent" />

                    <div className="flex py-2.5 text-[11px] sm:text-xs font-medium tracking-wide">
                        <div className="flex w-max animate-marquee hover:[animation-play-state:paused]">
                            {/* Piste 1 */}
                            <div className="flex shrink-0 items-center gap-8 pr-8">
                                {trackItems.map((item, idx) => (
                                    <span key={`t1-${idx}`} className="inline-flex items-center gap-3 whitespace-nowrap">
                                        <span>{item}</span>
                                        <span className="text-accent text-[10px] opacity-80" aria-hidden="true">✦</span>
                                    </span>
                                ))}
                            </div>
                            {/* Piste 2 (identique pour défilement continu vers la gauche sans coupure) */}
                            <div className="flex shrink-0 items-center gap-8 pr-8" aria-hidden="true">
                                {trackItems.map((item, idx) => (
                                    <span key={`t2-${idx}`} className="inline-flex items-center gap-3 whitespace-nowrap">
                                        <span>{item}</span>
                                        <span className="text-accent text-[10px] opacity-80">✦</span>
                                    </span>
                                ))}
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={() => setBannerVisible(false)}
                        aria-label="Fermer le bandeau d'annonce"
                        className="absolute right-2 sm:right-3 top-1/2 -translate-y-1/2 z-20 flex h-6 w-6 items-center justify-center rounded-md text-white/50 hover:bg-white/10 hover:text-white transition-colors text-xs"
                    >
                        ✕
                    </button>
                </aside>
            )}

            <Drawer open={drawerOpen} onClose={() => setDrawerOpen(false)} categories={categories}
                    marketplaceEnabled={marketplaceEnabled} user={auth?.user} />

            {flashMessage && (
                <div className="mx-auto w-full max-w-2xl px-4 pt-4">
                    <Alert tone={flashMessage.tone}>{flashMessage.text}</Alert>
                </div>
            )}

            <main>{children}</main>

            <Footer />

            {/* Bouton Flottant WhatsApp (identique au modèle de référence) */}
            <a
                href="https://wa.me/22900000000"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Discutons sur WhatsApp"
                className="fixed bottom-5 right-5 z-50 inline-flex items-center gap-2 rounded-full bg-[#25D366] px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-[0_4px_18px_rgba(37,211,102,0.45)] transition-all duration-200 hover:scale-105 hover:bg-[#20ba5a] active:scale-95"
            >
                <svg className="h-4 sm:h-5 w-4 sm:w-5 fill-current" viewBox="0 0 24 24">
                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.969.544 1.884.821 2.796.821 3.183 0 5.769-2.586 5.77-5.766.001-3.182-2.585-5.768-5.77-5.768zm3.393 8.354c-.149.421-.737.77-1.025.82-.288.05-.62.069-1.921-.456-1.558-.63-2.57-2.186-2.651-2.29-.081-.104-.64-.852-.64-1.624 0-.772.404-1.15.548-1.304.144-.154.316-.192.422-.192.106 0 .213.001.306.006.098.005.231-.038.361.274.135.324.462 1.127.502 1.21.04.082.067.177.013.286-.054.108-.081.176-.161.27-.081.095-.17.212-.243.285-.081.081-.166.17-.071.334.095.163.422.697.906 1.128.623.555 1.149.728 1.312.809.163.081.258.072.354-.038.096-.11.41-.478.52-.642.11-.164.22-.137.369-.082.149.055.945.446 1.107.527.162.081.27.122.31.19.04.068.04.394-.109.815z" />
                </svg>
                <span>Discutons sur WhatsApp</span>
            </a>
        </div>
    );
}
