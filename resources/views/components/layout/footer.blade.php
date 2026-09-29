<footer class="mt-24 border-t border-line-strong/70 bg-surface/90">
    <div class="mx-auto max-w-[1280px] px-4 py-14 sm:px-6">
        <div class="grid grid-cols-2 gap-8 sm:grid-cols-4">
            <div class="col-span-2 sm:col-span-1">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-line-strong/60 bg-white p-1 shadow-2xs">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo MALKUTHAR" class="h-full w-full object-contain">
                    </div>
                    <div class="flex flex-col">
                        <span class="font-display text-[15px] font-extrabold tracking-[0.16em] text-ink leading-tight">MALKUTHAR</span>
                        <span class="text-[9px] font-extrabold tracking-[0.22em] text-accent uppercase leading-none mt-0.5">Maison & Marché</span>
                    </div>
                </a>
                <p class="mt-3 max-w-xs text-xs leading-relaxed text-ink-2">
                    Produits digitaux, créations d'exception et services spécialisés — une seule maison d'honneur.
                </p>
                <ul class="mt-4 space-y-2 text-xs font-medium text-ink-2">
                    <li><a href="{{ route('about') }}" class="transition-colors hover:text-accent">À propos de la Maison</a></li>
                    <li><a href="{{ route('marketplace') }}" class="transition-colors hover:text-accent">Vendre sur la Marketplace</a></li>
                    <li><a href="{{ route('contact') }}" class="transition-colors hover:text-accent">Contact & Support</a></li>
                </ul>
            </div>

            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-accent">La Boutique</p>
                <ul class="mt-3.5 space-y-2.5 text-xs text-ink-2">
                    <li><a href="{{ url('/boutique?type=digital') }}" class="transition-colors hover:text-ink">Produits digitaux</a></li>
                    <li><a href="{{ url('/boutique?type=physical') }}" class="transition-colors hover:text-ink">Produits physiques</a></li>
                    <li><a href="{{ url('/boutique?type=service') }}" class="transition-colors hover:text-ink">Services & Consultations</a></li>
                </ul>
            </div>

            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-accent">Programmes</p>
                <ul class="mt-3.5 space-y-2.5 text-xs text-ink-2">
                    <li><a href="{{ route('affiliation') }}" class="transition-colors hover:text-ink">Affiliation — Découvrir</a></li>
                    <li><a href="{{ route('register.affiliate') }}" class="transition-colors hover:text-ink">Devenir affilié officiel</a></li>
                    <li><a href="{{ route('register.vendor') }}" class="transition-colors hover:text-ink">Devenir vendeur partenaire</a></li>
                    <li><a href="{{ route('affiliate.program-terms') }}" class="transition-colors hover:text-ink">Conditions du programme</a></li>
                </ul>
            </div>

            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-accent">Informations</p>
                <ul class="mt-3.5 space-y-2.5 text-xs text-ink-2">
                    <li><a href="{{ url('/mentions-legales') }}" class="transition-colors hover:text-ink">Conditions Générales de Vente</a></li>
                    <li><a href="{{ url('/confidentialite') }}" class="transition-colors hover:text-ink">Politique de confidentialité</a></li>
                    <li><a href="{{ url('/cookies') }}" class="transition-colors hover:text-ink">Gestion des cookies</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-2 border-t border-line pt-6 text-xs text-ink-3 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} MALKUTHAR SARL. Tous droits réservés.</p>
            <p class="font-medium text-ink-2">Paiement sécurisé crypté — FedaPay · CinetPay · Mobile Money</p>
        </div>
    </div>
</footer>
