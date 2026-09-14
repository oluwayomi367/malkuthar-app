import { useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import Alert from '../Components/ui/Alert';
import { cartCount } from '../lib/cart';

function NavLink({ href, active, children }) {
    return (
        <Link href={href} className={['rounded-lg px-3 py-2 transition-colors', active ? 'font-medium text-ink' : 'text-ink-2 hover:text-ink'].join(' ')}>
            {children}
        </Link>
    );
}

function MegaMenu({ categories, open, setOpen }) {
    if (!open) return null;

    return (
        <div onMouseEnter={() => setOpen(true)} onMouseLeave={() => setOpen(false)}
             className="absolute inset-x-0 top-full hidden px-4 pt-2 sm:px-6 lg:block">
            <div className="mx-auto max-w-[1180px] overflow-hidden rounded-2xl border border-line bg-surface shadow-[0_16px_44px_-24px_rgba(46,44,40,0.35)]">
                <div className="grid grid-cols-3 gap-2 p-4">
                    {categories.map((c) => (
                        <Link key={c.type} href={route('catalog.index', { type: c.type })} onClick={() => setOpen(false)}
                              className="group flex gap-3 rounded-xl border border-transparent p-3 transition-colors hover:border-line hover:bg-ground-alt">
                            <span className="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-line bg-ground-alt text-accent">
                                <svg className="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"
                                     dangerouslySetInnerHTML={{ __html: c.icon }} />
                            </span>
                            <span className="min-w-0">
                                <span className="block font-display text-[15px] font-semibold text-ink">{c.label}</span>
                                <span className="mt-1 block text-[12.5px] leading-snug text-ink-2">{c.text}</span>
                            </span>
                        </Link>
                    ))}
                </div>
                <div className="flex items-center justify-between border-t border-line bg-ground-alt/60 px-5 py-3 text-sm">
                    <span className="text-ink-3">Trois voies, une maison.</span>
                    <Link href={route('catalog.index')} onClick={() => setOpen(false)} className="font-semibold text-accent hover:underline">
                        Voir toute la boutique →
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
            <div className="absolute inset-y-0 left-0 flex w-[86%] max-w-sm flex-col border-r border-black/[0.08] bg-ground/95 backdrop-blur-xl">
                <div className="flex h-14 items-center justify-between border-b border-black/[0.06] px-4">
                    <span className="font-display text-[15px] font-bold tracking-[0.18em] text-ink">MALKUTHAR</span>
                    <button type="button" onClick={onClose} aria-label="Fermer"
                            className="-mr-2 inline-flex h-11 w-11 items-center justify-center text-ink">
                        <svg className="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6">
                            <path d="M5 5l10 10M15 5L5 15" strokeLinecap="round" />
                        </svg>
                    </button>
                </div>

                <nav className="flex-1 overflow-y-auto px-4 py-2">
                    <Link href="/" onClick={onClose} className="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">Accueil</Link>

                    <button type="button" onClick={() => setProdOpen((v) => !v)}
                            className="flex w-full items-center justify-between rounded-lg px-2 py-3 text-left text-[15px] text-ink hover:bg-black/[0.04]">
                        Produits
                        <svg className={['h-4 w-4 text-ink-3 transition-transform', prodOpen ? 'rotate-180' : ''].join(' ')} viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5">
                            <path d="M4 6l4 4 4-4" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                    </button>
                    {prodOpen && (
                        <div className="ml-2 border-l border-line pl-3">
                            <Link href={route('catalog.index')} onClick={onClose} className="block rounded-lg px-2 py-2.5 text-sm text-ink-2 hover:bg-black/[0.04] hover:text-ink">Toute la boutique</Link>
                            {categories.map((c) => (
                                <Link key={c.type} href={route('catalog.index', { type: c.type })} onClick={onClose}
                                      className="block rounded-lg px-2 py-2.5 text-sm text-ink-2 hover:bg-black/[0.04] hover:text-ink">{c.label}</Link>
                            ))}
                        </div>
                    )}

                    <Link href={route('affiliation')} onClick={onClose} className="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">Affiliation</Link>
                    <Link href={marketplaceEnabled ? route('marketplace.shop') : route('marketplace')} onClick={onClose} className="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">Marketplace</Link>
                    <Link href={route('about')} onClick={onClose} className="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">À propos</Link>
                    <Link href={route('contact')} onClick={onClose} className="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">Contact</Link>

                    <div className="my-2 border-t border-black/[0.06]" />
                    {user ? (
                        <>
                            <a href="/mon-compte" className="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">Mon compte</a>
                            <form method="POST" action={route('logout')}>
                                <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.content} />
                                <button className="block w-full rounded-lg px-2 py-3 text-left text-[15px] text-ink hover:bg-black/[0.04]">Se déconnecter</button>
                            </form>
                        </>
                    ) : (
                        <>
                            <a href={route('login')} className="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">Connexion</a>
                            <a href={route('register')} className="block rounded-lg px-2 py-3 text-[15px] text-ink hover:bg-black/[0.04]">Créer un compte</a>
                        </>
                    )}
                </nav>

                <div className="border-t border-black/[0.06] px-4 py-3 text-xs text-ink-3">
                    <a href={route('affiliate.program-terms')} className="hover:text-ink">Conditions</a>
                </div>
            </div>
        </div>
    );
}

function Footer() {
    return (
        <footer className="mt-24 border-t border-black/[0.06] bg-ground-alt/60">
            <div className="mx-auto max-w-[1180px] px-4 py-14 sm:px-6">
                <div className="grid grid-cols-2 gap-8 sm:grid-cols-4">
                    <div className="col-span-2 sm:col-span-1">
                        <p className="font-display text-[15px] font-bold tracking-[0.18em] text-ink">MALKUTHAR</p>
                        <p className="mt-2 max-w-xs text-sm text-ink-2">Produits digitaux, produits physiques et services — une seule maison.</p>
                        <ul className="mt-4 space-y-2 text-sm text-ink-2">
                            <li><Link href={route('about')} className="transition-colors hover:text-ink">À propos</Link></li>
                            <li><Link href={route('marketplace')} className="transition-colors hover:text-ink">Vendre sur MALKUTHAR</Link></li>
                            <li><Link href={route('contact')} className="transition-colors hover:text-ink">Contact</Link></li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-[10.5px] font-semibold uppercase tracking-[0.1em] text-ink-3">Boutique</p>
                        <ul className="mt-3 space-y-2 text-sm text-ink-2">
                            <li><Link href="/boutique?type=digital" className="hover:text-ink">Digitaux</Link></li>
                            <li><Link href="/boutique?type=physical" className="hover:text-ink">Physiques</Link></li>
                            <li><Link href="/boutique?type=service" className="hover:text-ink">Services</Link></li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-[10.5px] font-semibold uppercase tracking-[0.1em] text-ink-3">Programme</p>
                        <ul className="mt-3 space-y-2 text-sm text-ink-2">
                            <li><Link href={route('affiliation')} className="hover:text-ink">Comment ça marche</Link></li>
                            <li><a href={route('register')} className="hover:text-ink">Devenir affilié</a></li>
                            <li><a href={route('affiliate.program-terms')} className="hover:text-ink">Conditions</a></li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-[10.5px] font-semibold uppercase tracking-[0.1em] text-ink-3">Légal</p>
                        <ul className="mt-3 space-y-2 text-sm text-ink-2">
                            <li><a href="/mentions-legales" className="hover:text-ink">CGV</a></li>
                            <li><a href="/confidentialite" className="hover:text-ink">Confidentialité</a></li>
                            <li><a href="/cookies" className="hover:text-ink">Cookies</a></li>
                        </ul>
                    </div>
                </div>
                <div className="mt-10 flex flex-col gap-2 border-t border-black/[0.06] pt-6 text-xs text-ink-3 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {new Date().getFullYear()} MALKUTHAR SARL. Tous droits réservés.</p>
                    <p>Paiement sécurisé — FedaPay · CinetPay</p>
                </div>
            </div>
        </footer>
    );
}

/** Layout persistant Inertia — navbar/drawer/footer ne se remontent pas
 * entre deux pages de la vitrine (voir chaque Page.jsx : `Page.layout = ...`). */
export default function StorefrontLayout({ children }) {
    const { props, url } = usePage();
    const { categories = [], marketplaceEnabled, auth, flash } = props;

    const [drawerOpen, setDrawerOpen] = useState(false);
    const [megaOpen, setMegaOpen] = useState(false);
    const [count, setCount] = useState(0);

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
            <header className="sticky top-0 z-40 border-b border-line bg-ground/85 backdrop-blur-xl">
                <div className="relative mx-auto flex h-16 max-w-[1180px] items-center gap-4 px-4 sm:px-6 lg:gap-6">
                    <button type="button" onClick={() => setDrawerOpen(true)} aria-label="Ouvrir le menu"
                            className="-ml-2 inline-flex h-11 w-11 items-center justify-center text-ink lg:hidden">
                        <svg className="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.6">
                            <path d="M3 6h14M3 10h14M3 14h14" strokeLinecap="round" />
                        </svg>
                    </button>

                    <Link href="/" className="font-display text-[15px] font-bold tracking-[0.16em] text-ink">MALKUTHAR</Link>

                    <nav className="hidden items-center gap-0.5 text-sm lg:flex">
                        <NavLink href="/" active={url === '/'}>Accueil</NavLink>

                        <button type="button" onClick={() => setMegaOpen((v) => !v)} onMouseEnter={() => setMegaOpen(true)}
                                aria-expanded={megaOpen}
                                className={['inline-flex items-center gap-1 rounded-lg px-3 py-2 transition-colors hover:text-ink', megaOpen ? 'text-ink' : 'text-ink-2'].join(' ')}>
                            Produits
                            <svg className={['h-3.5 w-3.5 transition-transform', megaOpen ? 'rotate-180' : ''].join(' ')} viewBox="0 0 16 16" fill="none" stroke="currentColor" strokeWidth="1.5">
                                <path d="M4 6l4 4 4-4" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                        </button>

                        <NavLink href={route('affiliation')} active={is('/affiliation') || is('/programme-affiliation')}>Affiliation</NavLink>
                        <NavLink href={marketplaceEnabled ? route('marketplace.shop') : route('marketplace')} active={is('/place-de-marche') || is('/marketplace')}>Marketplace</NavLink>
                        <NavLink href={route('about')} active={is('/a-propos')}>À propos</NavLink>
                        <NavLink href={route('contact')} active={is('/contact')}>Contact</NavLink>
                    </nav>

                    <div className="ml-auto flex items-center gap-1">
                        <a href={auth?.user ? '/mon-compte' : route('login')}
                           className="hidden h-11 items-center px-3 text-sm text-ink-2 transition-colors hover:text-ink sm:inline-flex">
                            {auth?.user ? 'Mon compte' : 'Connexion'}
                        </a>
                        <Link href={route('cart')} aria-label="Panier" className="relative inline-flex h-11 w-11 items-center justify-center text-ink">
                            <svg className="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.5">
                                <path d="M3 4h2l1.6 9.2a1.5 1.5 0 0 0 1.48 1.24h6.84a1.5 1.5 0 0 0 1.47-1.2L18.5 7H6" strokeLinecap="round" strokeLinejoin="round" />
                                <circle cx="8.5" cy="17" r="1" /><circle cx="15.5" cy="17" r="1" />
                            </svg>
                            {count > 0 && (
                                <span className="absolute right-1 top-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-accent px-1 text-[10px] font-bold text-white">
                                    {count}
                                </span>
                            )}
                        </Link>
                    </div>

                    <MegaMenu categories={categories} open={megaOpen} setOpen={setMegaOpen} />
                </div>
            </header>

            <Drawer open={drawerOpen} onClose={() => setDrawerOpen(false)} categories={categories}
                    marketplaceEnabled={marketplaceEnabled} user={auth?.user} />

            {flashMessage && (
                <div className="mx-auto w-full max-w-2xl px-4 pt-4">
                    <Alert tone={flashMessage.tone}>{flashMessage.text}</Alert>
                </div>
            )}

            <main>{children}</main>

            <Footer />
        </div>
    );
}
