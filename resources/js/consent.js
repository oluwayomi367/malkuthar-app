/**
 * Consentement aux cookies — commun aux pages React (app.jsx) et Blade (app.js).
 *
 * Les scripts de mesure et de publicité sont rendus par
 * resources/views/partials/tracking-head.blade.php en
 * <script type="text/plain" data-consent="analytics|ads"> : inertes tant que
 * le visiteur n'a pas accepté la catégorie correspondante. Le choix est gardé
 * 6 mois dans le cookie `mal_consent`. Tout élément portant
 * `data-cookie-settings` rouvre la fenêtre de réglages.
 */

const COOKIE = 'mal_consent';
const MAX_AGE = 60 * 60 * 24 * 182;
const VERSION = 1;

const CATEGORIES = {
    analytics: { label: "Mesure d'audience", text: 'Comprendre comment le site est utilisé pour l\'améliorer (Google Analytics, Google Tag Manager).' },
    ads: { label: 'Publicité', text: 'Mesurer l\'efficacité de nos campagnes sur les réseaux sociaux (Meta, TikTok).' },
};

function readChoice() {
    const raw = document.cookie.split('; ').find((c) => c.startsWith(COOKIE + '='));
    if (!raw) return null;
    try {
        const value = JSON.parse(decodeURIComponent(raw.slice(COOKIE.length + 1)));
        return value?.v === VERSION ? value : null;
    } catch {
        return null;
    }
}

function saveChoice(choice) {
    const value = encodeURIComponent(JSON.stringify({ v: VERSION, analytics: !!choice.analytics, ads: !!choice.ads, at: new Date().toISOString() }));
    const secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${COOKIE}=${value}; Max-Age=${MAX_AGE}; Path=/; SameSite=Lax${secure}`;
}

/** Active les scripts en attente des catégories acceptées (une seule fois chacun). */
function activate(choice) {
    document.querySelectorAll('script[type="text/plain"][data-consent]').forEach((pending) => {
        if (!choice[pending.dataset.consent]) return;
        const script = document.createElement('script');
        for (const { name, value } of pending.attributes) {
            if (name !== 'type' && name !== 'data-consent') script.setAttribute(name, value);
        }
        script.text = pending.text;
        pending.replaceWith(script);
    });
}

/** Catégories pour lesquelles le site a réellement un outil configuré. */
const activeCategories = () => new Set([...document.querySelectorAll('script[data-consent]')].map((s) => s.dataset.consent));

const BTN = 'inline-flex min-h-11 items-center justify-center rounded-full px-5 text-sm font-semibold transition-colors';
const BTN_MAIN = `${BTN} bg-deep text-white hover:bg-deep-2`;
const BTN_ALT = `${BTN} border border-line-strong bg-surface text-ink hover:border-ink`;

let root = null;

function close() {
    root?.remove();
    root = null;
}

function decide(choice) {
    saveChoice(choice);
    activate(choice);
    close();
}

function el(tag, className, html) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (html !== undefined) node.innerHTML = html;
    return node;
}

/** Bandeau compact en bas d'écran (premier passage). */
function renderBanner() {
    close();
    root = el('div', 'fixed inset-x-0 bottom-0 z-[70] p-3 sm:bottom-4 sm:left-4 sm:right-auto sm:max-w-md sm:p-0');
    const card = el('section', 'rounded-2xl border border-line bg-surface p-5 font-sans text-ink shadow-[0_12px_40px_rgba(0,0,0,0.18)] sm:p-6');
    card.setAttribute('role', 'dialog');
    card.setAttribute('aria-labelledby', 'cookies-titre');
    card.setAttribute('aria-describedby', 'cookies-texte');
    card.innerHTML = `
        <h2 id="cookies-titre" class="text-base font-semibold text-ink">Vos choix en matière de cookies</h2>
        <p id="cookies-texte" class="mt-2 text-sm leading-relaxed text-ink-2">
            Nous utilisons des cookies nécessaires au fonctionnement du site (connexion, panier, parrainage).
            Avec votre accord, nous utilisons aussi des cookies de mesure d'audience et de publicité.
            <a href="/cookies" class="font-medium text-ink underline underline-offset-2 hover:text-accent-2">En savoir plus</a>
        </p>`;
    const actions = el('div', 'mt-4 flex flex-wrap gap-2');
    const accept = el('button', `${BTN_MAIN} flex-1`, 'Tout accepter');
    const refuse = el('button', `${BTN_ALT} flex-1`, 'Tout refuser');
    const custom = el('button', 'inline-flex min-h-11 w-full items-center justify-center text-sm font-medium text-ink-2 underline underline-offset-2 hover:text-ink', 'Personnaliser');
    [accept, refuse, custom].forEach((b) => (b.type = 'button'));
    accept.addEventListener('click', () => decide({ analytics: true, ads: true }));
    refuse.addEventListener('click', () => decide({ analytics: false, ads: false }));
    custom.addEventListener('click', () => renderSettings());
    actions.append(refuse, accept, custom);
    card.append(actions);
    root.append(card);
    document.body.append(root);
}

/** Fenêtre de réglages détaillés (bouton « Personnaliser » ou lien « Gérer les cookies »). */
function renderSettings() {
    close();
    const current = readChoice() ?? { analytics: false, ads: false };
    const active = activeCategories();

    root = el('div', 'fixed inset-0 z-[70] flex items-end justify-center bg-ink/50 p-3 sm:items-center sm:p-6');
    const dialog = el('section', 'max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-surface p-5 font-sans text-ink shadow-[0_12px_40px_rgba(0,0,0,0.25)] sm:p-7');
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('aria-labelledby', 'cookies-reglages-titre');

    const row = (id, label, text, { checked = false, locked = false, note = '' } = {}) => `
        <li class="flex items-start justify-between gap-4 py-4">
            <span class="min-w-0">
                <label for="${id}" class="block text-sm font-semibold text-ink">${label}</label>
                <span class="mt-1 block text-[13px] leading-relaxed text-ink-2">${text}${note ? ` <em class="not-italic text-ink-3">${note}</em>` : ''}</span>
            </span>
            <input id="${id}" type="checkbox" ${checked ? 'checked' : ''} ${locked ? 'disabled' : ''}
                   class="mt-1 h-5 w-5 shrink-0 cursor-pointer rounded border-line-strong accent-[var(--color-deep)] disabled:cursor-not-allowed">
        </li>`;

    dialog.innerHTML = `
        <div class="flex items-start justify-between gap-4">
            <h2 id="cookies-reglages-titre" class="text-lg font-semibold text-ink">Gérer les cookies</h2>
            <button type="button" data-close aria-label="Fermer" class="-m-2 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-ink-2 hover:bg-ground-alt hover:text-ink">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
        <p class="mt-2 text-sm leading-relaxed text-ink-2">Choisissez les cookies que vous acceptez. Vous pouvez changer d'avis à tout moment depuis le lien « Gérer les cookies » en bas de page.</p>
        <ul class="mt-2 divide-y divide-line">
            ${row('cookies-necessaires', 'Nécessaires', 'Connexion, sécurité des formulaires, langue, panier et parrainage. Indispensables au site.', { checked: true, locked: true, note: 'Toujours actifs.' })}
            ${Object.entries(CATEGORIES).map(([key, c]) => row(`cookies-${key}`, c.label, c.text, {
                checked: current[key],
                note: active.has(key) ? '' : 'Aucun outil de ce type n\'est actif pour le moment.',
            })).join('')}
        </ul>
        <div class="mt-5 flex flex-wrap gap-2">
            <button type="button" data-refuse class="${BTN_ALT} flex-1">Tout refuser</button>
            <button type="button" data-save class="${BTN_MAIN} flex-1">Enregistrer mes choix</button>
        </div>
        <p class="mt-4 text-center text-xs text-ink-3"><a href="/cookies" class="underline underline-offset-2 hover:text-ink">Liste détaillée des cookies</a></p>`;

    const previous = document.activeElement;
    const dismiss = () => {
        close();
        if (!readChoice()) renderBanner();
        else previous?.focus?.();
    };
    dialog.querySelector('[data-close]').addEventListener('click', dismiss);
    dialog.querySelector('[data-refuse]').addEventListener('click', () => decide({ analytics: false, ads: false }));
    dialog.querySelector('[data-save]').addEventListener('click', () => decide({
        analytics: dialog.querySelector('#cookies-analytics').checked,
        ads: dialog.querySelector('#cookies-ads').checked,
    }));
    root.addEventListener('click', (e) => { if (e.target === root) dismiss(); });
    root.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') dismiss();
        if (e.key !== 'Tab') return;
        // Garde le focus dans la fenêtre.
        const focusable = [...dialog.querySelectorAll('button, a, input:not([disabled])')];
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });

    root.append(dialog);
    document.body.append(root);
    dialog.querySelector('#cookies-analytics')?.focus();
}

function init() {
    if (window.__malConsentReady) return;
    window.__malConsentReady = true;

    const choice = readChoice();
    if (choice) activate(choice);
    else renderBanner();

    document.addEventListener('click', (e) => {
        const trigger = e.target.closest?.('[data-cookie-settings]');
        if (!trigger) return;
        e.preventDefault();
        renderSettings();
    });

    window.malCookieConsent = { open: renderSettings };
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();
