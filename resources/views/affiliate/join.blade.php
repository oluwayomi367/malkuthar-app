@extends('layouts.app')

@section('title', 'Devenir affilié')

@section('content')
<div class="mx-auto max-w-2xl">
    <header>
        <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Devenez affilié MALKUTHAR</h1>
        <p class="mt-3 text-[15px] leading-relaxed text-ink-2">Recommandez les produits digitaux MALKUTHAR et touchez une commission sur trois niveaux. Pas de second compte : votre espace client devient aussi votre espace affilié.</p>
    </header>

    <section class="mt-8" aria-labelledby="rates-title">
        <h2 id="rates-title" class="font-sans text-lg font-semibold text-ink">Vos commissions</h2>
        <ul class="mt-4 grid gap-4 sm:grid-cols-3">
            <li class="rounded-2xl border border-line bg-surface p-5">
                <p class="font-display text-[36px] font-semibold leading-none text-accent-2">15&nbsp;%</p>
                <p class="mt-3 text-sm font-semibold text-ink">Niveau 1</p>
                <p class="text-[13px] text-ink-2">Vos ventes directes</p>
            </li>
            <li class="rounded-2xl border border-line bg-surface p-5">
                <p class="font-display text-[36px] font-semibold leading-none text-ink">10&nbsp;%</p>
                <p class="mt-3 text-sm font-semibold text-ink">Niveau 2</p>
                <p class="text-[13px] text-ink-2">Ventes de vos filleuls</p>
            </li>
            <li class="rounded-2xl border border-line bg-surface p-5">
                <p class="font-display text-[36px] font-semibold leading-none text-ink">5&nbsp;%</p>
                <p class="mt-3 text-sm font-semibold text-ink">Niveau 3</p>
                <p class="text-[13px] text-ink-2">Niveau suivant</p>
            </li>
        </ul>
        <p class="mt-3 text-[13px] text-ink-3">Les produits physiques et les services ne génèrent pas de commission.</p>
    </section>

    <section class="mt-8 rounded-2xl border border-line bg-ground-alt p-5 sm:p-6" aria-labelledby="steps-title">
        <h2 id="steps-title" class="font-sans text-base font-semibold text-ink">Comment ça marche</h2>
        <ol class="mt-3 space-y-2 text-sm leading-relaxed text-ink-2">
            <li><span class="font-semibold text-ink">1.</span> Vous activez votre espace et recevez un lien personnel.</li>
            <li><span class="font-semibold text-ink">2.</span> Vous le partagez ; chaque vente éligible vous est attribuée.</li>
            <li><span class="font-semibold text-ink">3.</span> Les commissions validées s'ajoutent à votre solde, que vous pouvez retirer.</li>
        </ol>
    </section>

    <form method="POST" action="{{ route('affiliate.activate') }}" class="mt-8">
        @csrf
        <x-ui.button type="submit" variant="primary" :full="true">Activer mon espace affilié</x-ui.button>
    </form>

    <p class="mt-3 text-center text-[13px] text-ink-3">
        En activant, vous acceptez les
        <a href="{{ route('affiliate.program-terms') }}" class="font-semibold text-accent-2 underline decoration-accent/40 underline-offset-4 hover:decoration-accent">conditions du programme</a>.
    </p>
</div>
@endsection
