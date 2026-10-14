@php
    $heading = 'text-sm font-semibold text-night-ink';
    $list = 'mt-4 space-y-1 text-sm text-night-ink-2';
    $link = 'inline-flex min-h-9 items-center transition-colors hover:text-night-ink';
@endphp

<footer class="mt-24 bg-ink text-night-ink-2">
    <div class="mx-auto max-w-[1280px] px-4 py-14 sm:px-6 lg:py-16">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
            <div>
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-surface p-1.5">
                        <img src="{{ asset('images/logo.png') }}" alt="" class="h-full w-full object-contain">
                    </span>
                    <span class="font-display text-[22px] font-semibold tracking-[0.18em] text-night-ink">MALKUTHAR</span>
                </a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed">
                    Livres, formations, séances et créations de vendeurs vérifiés, réunis sur une même boutique.
                </p>
            </div>

            <div>
                <p class="{{ $heading }}">Boutique</p>
                <ul class="{{ $list }}">
                    <li><a href="{{ url('/boutique?type=digital') }}" class="{{ $link }}">Produits digitaux</a></li>
                    <li><a href="{{ url('/boutique?type=physical') }}" class="{{ $link }}">Produits physiques</a></li>
                    <li><a href="{{ url('/boutique?type=service') }}" class="{{ $link }}">Services et consultations</a></li>
                    <li><a href="{{ \App\Support\MarketplaceSettings::enabled() ? route('marketplace.shop') : route('marketplace') }}" class="{{ $link }}">Place de marché</a></li>
                </ul>
            </div>

            <div>
                <p class="{{ $heading }}">Programmes</p>
                <ul class="{{ $list }}">
                    <li><a href="{{ route('affiliation') }}" class="{{ $link }}">Le programme d'affiliation</a></li>
                    <li><a href="{{ route('register.affiliate') }}" class="{{ $link }}">Devenir affilié</a></li>
                    <li><a href="{{ route('register.vendor') }}" class="{{ $link }}">Devenir vendeur</a></li>
                    <li><a href="{{ route('affiliate.program-terms') }}" class="{{ $link }}">Conditions du programme</a></li>
                </ul>
            </div>

            <div>
                <p class="{{ $heading }}">Informations</p>
                <ul class="{{ $list }}">
                    <li><a href="{{ route('about') }}" class="{{ $link }}">À propos</a></li>
                    <li><a href="{{ route('contact') }}" class="{{ $link }}">Contact</a></li>
                    <li><a href="{{ url('/mentions-legales') }}" class="{{ $link }}">Conditions générales de vente</a></li>
                    <li><a href="{{ url('/confidentialite') }}" class="{{ $link }}">Politique de confidentialité</a></li>
                    <li><a href="{{ url('/cookies') }}" class="{{ $link }}">Gestion des cookies</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-12 flex flex-col gap-2 border-t border-night-line pt-6 text-xs sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} MALKUTHAR SARL. Tous droits réservés.</p>
            <p>Paiement par FedaPay, CinetPay et Mobile Money</p>
        </div>
    </div>
</footer>
