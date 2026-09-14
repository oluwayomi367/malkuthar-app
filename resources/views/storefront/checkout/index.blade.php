@extends('layouts.storefront')

@section('title', 'Panier & paiement')
@section('seo_description', 'Votre panier MALKUTHAR et le paiement sécurisé.')

@section('content')
<div class="mx-auto max-w-[1180px] px-4 py-10 sm:px-6"
     x-data="{
        cart: JSON.parse(localStorage.getItem('mal_cart') || '[]'),
        email: '', provider: 'fedapay', method: 'mobile_money', coupon: '',
        ship: { first_name:'', last_name:'', phone:'', country:'', city:'', neighborhood:'', address:'' },
        loading: false, error: '',
        get hasPhysical() { return this.cart.some(i => i.type === 'physical'); },
        get hasService() { return this.cart.some(i => i.type === 'service'); },
        get subtotal() { return this.cart.reduce((s,i) => s + i.price * i.quantity, 0); },
        get currency() { return this.cart[0]?.currency || 'FCFA'; },
        money(n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n)); },
        persist() { localStorage.setItem('mal_cart', JSON.stringify(this.cart)); if (window.Alpine) Alpine.store('cart').sync(); },
        remove(idx) { this.cart.splice(idx,1); this.persist(); },
        setQty(idx, q) { this.cart[idx].quantity = Math.max(1, q); this.persist(); },
        async pay() {
            this.error = ''; this.loading = true;
            const payload = {
                payment_method: this.provider === 'cod' ? 'cash_on_delivery' : this.method,
                payment_provider: this.provider,
                email: this.email || null, coupon_code: this.coupon || null,
                items: this.cart.map(i => {
                    const it = { product_id: i.product_id, quantity: i.quantity };
                    if (i.reservation_date) { it.reservation_date = i.reservation_date; it.reservation_time = i.reservation_time; }
                    if (i.variant_id) it.variant_id = i.variant_id;
                    if (i.variant_sku) it.variant_sku = i.variant_sku;
                    return it;
                }),
            };
            if (this.hasPhysical) payload.shipping_address = this.ship;
            try {
                const r = await fetch('/checkout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify(payload),
                });
                const data = await r.json();
                if (!r.ok) { this.error = data.message || 'Une erreur est survenue.'; this.loading = false; return; }
                localStorage.removeItem('mal_cart');
                if (data.data?.payment?.url) { window.location = data.data.payment.url; }
                else { window.location = '/checkout/return/' + data.data.order_id; }
            } catch (e) { this.error = 'Erreur réseau. Réessayez.'; this.loading = false; }
        }
     }">

    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Panier</h1>
        <a href="{{ route('catalog.index') }}" class="text-sm text-ink-2 transition-colors hover:text-ink">‹ Continuer mes achats</a>
    </div>

    {{-- ===== Panier vide ===== --}}
    <template x-if="!cart.length">
        <div class="rounded-2xl border border-dashed border-black/12 bg-surface px-6 py-16 text-center">
            <p class="font-display font-semibold text-ink">Votre panier est vide</p>
            <p class="mx-auto mt-1 max-w-sm text-sm text-ink-2">Parcourez la boutique pour ajouter des produits digitaux, physiques ou des services.</p>
            <a href="{{ route('catalog.index') }}"
               class="mt-5 inline-flex h-11 items-center justify-center rounded-xl bg-accent px-5 text-sm font-semibold text-white shadow-[0_10px_34px_-10px] shadow-black/10">
                Explorer la boutique
            </a>
        </div>
    </template>

    {{-- ===== Panier rempli ===== --}}
    <template x-if="cart.length">
        <div class="grid gap-8 lg:grid-cols-[1.5fr_1fr] lg:gap-12">

            {{-- Colonne gauche : lignes + formulaire --}}
            <div class="space-y-6">
                {{-- Lignes --}}
                <ul class="space-y-3">
                    <template x-for="(i, idx) in cart" :key="idx">
                        <li class="flex items-start gap-4 rounded-2xl border border-black/10 bg-surface p-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-black/10 bg-gradient-to-br from-black/[0.05] to-transparent text-ink-3">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4">
                                    <rect x="3" y="4" width="14" height="12" rx="2"/><path d="M3 13l4-4 3 3 3-3 4 4"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[15px] font-medium text-ink" x-text="i.name"></p>
                                <p class="mt-0.5 text-xs text-ink-3">
                                    <span x-text="({digital:'Digital',physical:'Physique',service:'Service'})[i.type]"></span>
                                    <template x-if="i.reservation_date"><span x-text="' · ' + i.reservation_date + ' ' + i.reservation_time"></span></template>
                                    <template x-if="i.variant_sku"><span x-text="' · ' + i.variant_sku"></span></template>
                                </p>
                                <div class="mt-2 flex items-center gap-3">
                                    <div class="inline-flex items-center rounded-lg border border-black/10">
                                        <button type="button" x-on:click="setQty(idx, i.quantity - 1)" class="flex h-8 w-8 items-center justify-center text-ink-2 hover:text-ink" aria-label="Réduire">−</button>
                                        <span class="w-6 text-center text-sm font-semibold text-ink" x-text="i.quantity"></span>
                                        <button type="button" x-on:click="setQty(idx, i.quantity + 1)" class="flex h-8 w-8 items-center justify-center text-ink-2 hover:text-ink" aria-label="Augmenter">+</button>
                                    </div>
                                    <button type="button" x-on:click="remove(idx)" class="text-xs text-ink-3 underline decoration-rust/50 underline-offset-2 hover:text-rust">Retirer</button>
                                </div>
                            </div>
                            <p class="shrink-0 text-sm font-semibold text-ink" x-text="money(i.price * i.quantity) + ' ' + i.currency"></p>
                        </li>
                    </template>
                </ul>

                {{-- Coordonnées --}}
                <div class="rounded-2xl border border-black/10 bg-surface p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Vos coordonnées</p>
                    <div class="mt-3 grid gap-3">
                        <input x-model="email" type="email" placeholder="Email — pour le reçu et l'accès digital"
                               class="h-11 rounded-xl border border-black/10 bg-surface px-3.5 text-[15px] text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25">
                    </div>
                </div>

                {{-- Livraison (si physique) --}}
                <template x-if="hasPhysical">
                    <div class="rounded-2xl border border-black/10 bg-surface p-5">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Adresse de livraison</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <input x-model="ship.first_name" placeholder="Prénom" class="h-11 rounded-xl border border-black/10 bg-surface px-3.5 text-[15px] text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25">
                            <input x-model="ship.last_name" placeholder="Nom" class="h-11 rounded-xl border border-black/10 bg-surface px-3.5 text-[15px] text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25">
                            <input x-model="ship.phone" placeholder="Téléphone" class="h-11 rounded-xl border border-black/10 bg-surface px-3.5 text-[15px] text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25">
                            <input x-model="ship.country" placeholder="Pays" class="h-11 rounded-xl border border-black/10 bg-surface px-3.5 text-[15px] text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25">
                            <input x-model="ship.city" placeholder="Ville" class="h-11 rounded-xl border border-black/10 bg-surface px-3.5 text-[15px] text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25">
                            <input x-model="ship.neighborhood" placeholder="Quartier (facultatif)" class="h-11 rounded-xl border border-black/10 bg-surface px-3.5 text-[15px] text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25">
                            <input x-model="ship.address" placeholder="Adresse complète" class="h-11 rounded-xl border border-black/10 bg-surface px-3.5 text-[15px] text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25 sm:col-span-2">
                        </div>
                    </div>
                </template>

                {{-- Réservation (si service) --}}
                <template x-if="hasService">
                    <div class="rounded-2xl border border-black/10 bg-surface p-5 text-sm text-ink-2">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Réservation</p>
                        <ul class="mt-3 space-y-1">
                            <template x-for="(i, idx) in cart.filter(x => x.type === 'service')" :key="idx">
                                <li><span class="text-ink" x-text="i.name"></span> — <span x-text="i.reservation_date + ' à ' + i.reservation_time"></span></li>
                            </template>
                        </ul>
                        <p class="mt-2 text-xs text-ink-3">Créneau choisi sur la fiche du service. Pour le modifier, retirez la ligne et re-réservez.</p>
                    </div>
                </template>

                {{-- Paiement --}}
                <div class="rounded-2xl border border-black/10 bg-surface p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Paiement</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition-colors"
                               :class="provider === 'fedapay' ? 'border-accent bg-accent/10' : 'border-black/10 hover:border-black/20'">
                            <input type="radio" x-model="provider" value="fedapay" class="text-accent focus:ring-accent/30">
                            <span class="text-sm font-medium text-ink">FedaPay</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition-colors"
                               :class="provider === 'cinetpay' ? 'border-accent bg-accent/10' : 'border-black/10 hover:border-black/20'">
                            <input type="radio" x-model="provider" value="cinetpay" class="text-accent focus:ring-accent/30">
                            <span class="text-sm font-medium text-ink">CinetPay</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition-colors sm:col-span-2"
                               :class="provider === 'cod' ? 'border-accent bg-accent/10' : 'border-black/10 hover:border-black/20'">
                            <input type="radio" x-model="provider" value="cod" class="text-accent focus:ring-accent/30">
                            <span class="text-sm font-medium text-ink">Paiement à la livraison</span>
                        </label>
                    </div>
                    <p class="mt-2 text-xs text-ink-3"
                       x-text="provider === 'cod'
                           ? 'Vous réglez en espèces à la réception (ou lors du rendez-vous). Votre commande est préparée dès validation.'
                           : 'Mobile Money ou carte selon votre pays, à l\'étape suivante.'"></p>
                </div>
            </div>

            {{-- Colonne droite : récap (collante sur lg) --}}
            <div class="lg:sticky lg:top-20 lg:self-start">
                <div class="rounded-2xl border border-black/10 bg-black/[0.04] p-5 backdrop-blur-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-3">Récapitulatif</p>

                    <div class="mt-3 flex items-center gap-2">
                        <input x-model="coupon" type="text" placeholder="Code promo"
                               class="h-10 flex-1 rounded-xl border border-black/10 bg-surface px-3 text-sm text-ink placeholder:text-ink-3 focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/25">
                    </div>

                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-ink-2">Sous-total</dt><dd class="text-ink" x-text="money(subtotal) + ' ' + currency"></dd></div>
                        <div class="flex justify-between text-ink-3"><dt>Livraison &amp; remise</dt><dd>calculées au paiement</dd></div>
                    </dl>

                    <div class="mt-4 flex items-center justify-between border-t border-black/10 pt-4">
                        <span class="font-display text-lg font-semibold text-ink">Total</span>
                        <span class="font-display text-lg font-semibold text-ink" x-text="money(subtotal) + ' ' + currency"></span>
                    </div>

                    <p x-show="error" x-cloak x-text="error" class="mt-3 rounded-xl border border-rust/40 bg-rust/10 p-3 text-sm text-rust"></p>

                    <button x-on:click="pay()" :disabled="loading"
                            class="mt-4 hidden h-12 w-full items-center justify-center rounded-xl bg-accent text-sm font-semibold text-white shadow-[0_12px_40px_-12px] shadow-black/10 transition-transform hover:-translate-y-0.5 disabled:opacity-50 lg:flex"
                            x-text="loading ? (provider === 'cod' ? 'Validation…' : 'Redirection…') : (provider === 'cod' ? 'Valider la commande' : 'Payer')"></button>
                </div>
            </div>

        </div>
    </template>

    {{-- Barre collée mobile --}}
    <template x-if="cart.length">
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-black/[0.08] bg-ground/85 px-4 py-3 backdrop-blur-xl lg:hidden"
             style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
            <div class="mx-auto flex max-w-[1180px] items-center gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-ink-3">Total</p>
                    <p class="font-semibold text-ink" x-text="money(subtotal) + ' ' + currency"></p>
                </div>
                <button x-on:click="pay()" :disabled="loading"
                        class="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-accent text-sm font-semibold text-white shadow-[0_10px_34px_-10px] shadow-black/10 disabled:opacity-50"
                        x-text="loading ? (provider === 'cod' ? 'Validation…' : 'Redirection…') : (provider === 'cod' ? 'Valider la commande' : 'Payer')"></button>
            </div>
        </div>
    </template>
    <div class="h-20 lg:hidden" x-show="cart.length" aria-hidden="true"></div>
</div>
@endsection
