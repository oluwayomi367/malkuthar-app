{{-- Gabarit commun aux pages légales (mentions légales, confidentialité, cookies). --}}
@extends('layouts.storefront')

@section('content')
<article class="mx-auto w-full max-w-[1280px] px-4 pb-20 pt-12 sm:px-6 sm:pt-16">
<div class="mx-auto max-w-prose">

    <div class="mb-8 rounded-xl border border-amber/30 bg-amber-tint px-4 py-3 text-sm leading-relaxed text-amber" role="note">
        Document de travail — doit être validé par un conseil juridique compétent
        pour chaque pays concerné avant publication (§81).
    </div>

    <h1 class="font-display text-[36px] font-semibold leading-tight text-ink sm:text-[44px]">@yield('legal_heading')</h1>
    <p class="mt-3 text-sm text-ink-3">Dernière mise à jour : {{ config('malkuthar.legal.updated_at') }}</p>

    @hasSection('legal_intro')
        <p class="mt-4 text-base leading-[1.7] text-ink-2">@yield('legal_intro')</p>
    @endif

    <nav aria-label="Pages légales" class="mt-6 flex flex-wrap gap-2 text-sm">
        @foreach ([['Mentions légales', 'legal.notice'], ['Confidentialité', 'legal.privacy'], ['Cookies', 'legal.cookies']] as [$label, $name])
            <a href="{{ route($name) }}" @if (request()->routeIs($name)) aria-current="page" @endif
               class="inline-flex min-h-10 items-center rounded-full border px-4 font-medium transition-colors {{ request()->routeIs($name) ? 'border-deep bg-deep text-white' : 'border-line-strong text-ink hover:border-ink' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    <div class="mt-10 space-y-10 border-t border-line pt-10 text-base leading-[1.7] text-ink-2 [&_a]:font-medium [&_a]:text-accent [&_a]:underline [&_a]:decoration-accent/40 [&_a]:underline-offset-4 hover:[&_a]:decoration-accent [&_h2]:font-display [&_h2]:text-[26px] [&_h2]:font-semibold [&_h2]:leading-snug [&_h2]:text-ink [&_li]:mt-1.5 [&_p]:mt-3 [&_strong]:font-semibold [&_strong]:text-ink [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:pl-5">
        @yield('legal_body')
    </div>
</div>
</article>
@endsection
