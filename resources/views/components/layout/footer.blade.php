<footer class="mt-24 border-t border-black/[0.06] bg-ground-alt/60">
    <div class="mx-auto max-w-[1180px] px-4 py-14 sm:px-6">
        <div class="grid grid-cols-2 gap-8 sm:grid-cols-4">
            <div class="col-span-2 sm:col-span-1">
                <p class="font-display text-[15px] font-bold tracking-[0.18em] text-ink">MALKUTHAR</p>
                <p class="mt-2 max-w-xs text-sm text-ink-2">
                    Produits digitaux, produits physiques et services — une seule maison.
                </p>
                <ul class="mt-4 space-y-2 text-sm text-ink-2">
                    <li><a href="{{ route('about') }}" class="transition-colors hover:text-ink">À propos</a></li>
                    <li><a href="{{ route('marketplace') }}" class="transition-colors hover:text-ink">Vendre sur MALKUTHAR</a></li>
                    <li><a href="{{ route('contact') }}" class="transition-colors hover:text-ink">Contact</a></li>
                </ul>
            </div>

            <div>
                <p class="text-[10.5px] font-semibold uppercase tracking-[0.1em] text-ink-3">Boutique</p>
                <ul class="mt-3 space-y-2 text-sm text-ink-2">
                    <li><a href="{{ url('/boutique?type=digital') }}" class="hover:text-ink">Digitaux</a></li>
                    <li><a href="{{ url('/boutique?type=physical') }}" class="hover:text-ink">Physiques</a></li>
                    <li><a href="{{ url('/boutique?type=service') }}" class="hover:text-ink">Services</a></li>
                </ul>
            </div>

            <div>
                <p class="text-[10.5px] font-semibold uppercase tracking-[0.1em] text-ink-3">Programme</p>
                <ul class="mt-3 space-y-2 text-sm text-ink-2">
                    <li><a href="{{ route('affiliation') }}" class="hover:text-ink">Comment ça marche</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-ink">Devenir affilié</a></li>
                    <li><a href="{{ route('affiliate.program-terms') }}" class="hover:text-ink">Conditions</a></li>
                </ul>
            </div>

            <div>
                <p class="text-[10.5px] font-semibold uppercase tracking-[0.1em] text-ink-3">Légal</p>
                <ul class="mt-3 space-y-2 text-sm text-ink-2">
                    <li><a href="{{ url('/mentions-legales') }}" class="hover:text-ink">CGV</a></li>
                    <li><a href="{{ url('/confidentialite') }}" class="hover:text-ink">Confidentialité</a></li>
                    <li><a href="{{ url('/cookies') }}" class="hover:text-ink">Cookies</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-2 border-t border-black/[0.06] pt-6 text-xs text-ink-3 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} MALKUTHAR SARL. Tous droits réservés.</p>
            <p>Paiement sécurisé — FedaPay · CinetPay</p>
        </div>
    </div>
</footer>
