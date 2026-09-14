@extends('layouts.storefront')

@section('title', 'Contact')
@section('seo_description', "Contactez l'équipe MALKUTHAR : questions sur une commande, un produit ou le programme d'affiliation.")

@section('content')
@php $c = config('malkuthar.contact'); @endphp

<div class="mx-auto max-w-[1180px] px-4 py-12 sm:px-6 sm:py-16">
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gold">Contact</p>
    <h1 class="mt-3 text-[28px] font-bold tracking-[-0.025em] text-ink sm:text-4xl">Une question&nbsp;?</h1>
    <p class="mt-3 max-w-lg text-ink-2">
        Écrivez-nous — une commande, un produit, le programme d'affiliation, ou autre chose.
        Nous répondons à l'adresse que vous indiquez.
    </p>

    <div class="mt-10 grid gap-10 lg:grid-cols-[1.4fr_1fr] lg:gap-16">
        {{-- Formulaire --}}
        <form method="POST" action="{{ route('contact.store') }}" class="grid gap-4">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field name="name" label="Votre nom" :required="true" autocomplete="name" />
                <x-ui.field name="email" label="Votre email" type="email" :required="true" autocomplete="email" />
            </div>

            <x-ui.select
                name="subject"
                label="Sujet"
                :required="true"
                :options="[
                    'commande' => 'Une commande',
                    'produit' => 'Un produit',
                    'affiliation' => 'Le programme d\'affiliation',
                    'vendeur' => 'Devenir vendeur (Marketplace)',
                    'autre' => 'Autre',
                ]"
                placeholder="Choisir un sujet…"
            />

            <x-ui.textarea name="message" label="Votre message" :rows="6" :required="true" />

            {{-- honeypot : caché aux humains, doit rester vide --}}
            <div class="hidden" aria-hidden="true">
                <label>Ne pas remplir <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <div>
                <x-ui.button variant="primary" type="submit">Envoyer le message</x-ui.button>
            </div>
        </form>

        {{-- Coordonnées --}}
        <aside>
            <x-ui.card>
                <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-ink-3">Coordonnées</p>

                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="text-ink-3">Email</dt>
                        <dd class="mt-0.5"><a href="mailto:{{ $c['email'] }}" class="font-medium text-ink underline decoration-gold underline-offset-4">{{ $c['email'] }}</a></dd>
                    </div>

                    @if (! empty($c['whatsapp']))
                        <div>
                            <dt class="text-ink-3">WhatsApp</dt>
                            <dd class="mt-0.5"><a href="https://wa.me/{{ preg_replace('/\D/', '', $c['whatsapp']) }}" class="font-medium text-ink underline decoration-gold underline-offset-4">{{ $c['whatsapp'] }}</a></dd>
                        </div>
                    @endif

                    @if (! empty($c['phone']))
                        <div>
                            <dt class="text-ink-3">Téléphone</dt>
                            <dd class="mt-0.5 font-medium text-ink">{{ $c['phone'] }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-ink-3">Horaires</dt>
                        <dd class="mt-0.5 text-ink-2">{{ $c['hours'] }}</dd>
                    </div>

                    <div>
                        <dt class="text-ink-3">Adresse</dt>
                        <dd class="mt-0.5 text-ink-2">{{ $c['company'] }} — {{ $c['address'] }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <p class="mt-4 text-xs text-ink-3">
                Pour le suivi d'une commande, connectez-vous à
                <a href="{{ url('/mon-compte') }}" class="underline decoration-gold underline-offset-2">votre espace client</a>.
            </p>
        </aside>
    </div>
</div>
@endsection
