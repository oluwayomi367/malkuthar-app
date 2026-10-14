<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-ground font-sans text-ink antialiased">
    @include('partials.tracking-body')

    <div class="grid min-h-screen lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">

        {{-- Panneau encre (desktop) : nom de la maison + phrase adaptée au parcours. --}}
        <aside class="hidden bg-ink text-night-ink lg:block">
            <div class="sticky top-0 flex h-screen flex-col justify-between px-12 py-12 xl:px-16">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3 self-start">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-surface p-1.5">
                        <img src="{{ asset('images/logo.png') }}" alt="" class="h-full w-full object-contain">
                    </span>
                    <span class="font-display text-[22px] font-semibold tracking-[0.2em] text-night-ink">MALKUTHAR</span>
                </a>

                <div class="max-w-md">
                    <p class="font-display text-[34px] font-medium leading-[1.15] text-night-ink">
                        @hasSection('auth_tagline') @yield('auth_tagline') @else Votre compte MALKUTHAR. @endif
                    </p>
                    <p class="mt-4 text-[15px] leading-relaxed text-night-ink-2">
                        @hasSection('auth_subtitle') @yield('auth_subtitle') @else Vos commandes, vos téléchargements, vos réservations et, si vous les avez activés, vos espaces affilié et vendeur. @endif
                    </p>
                    @hasSection('auth_panel_extra')
                        <div class="mt-8">@yield('auth_panel_extra')</div>
                    @endif
                </div>

                <p class="text-xs text-night-ink-2">© {{ date('Y') }} MALKUTHAR SARL</p>
            </div>
        </aside>

        {{-- Colonne formulaire --}}
        <div class="flex min-h-screen flex-col px-4 py-6 sm:px-10 lg:px-14 xl:px-20">
            <header class="flex items-center justify-between gap-4">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5 lg:hidden">
                    <img src="{{ asset('images/logo.png') }}" alt="" class="h-8 w-8 object-contain">
                    <span class="font-display text-[20px] font-semibold tracking-[0.18em] text-ink">MALKUTHAR</span>
                </a>
                <a href="{{ url('/') }}" class="ml-auto inline-flex min-h-11 items-center gap-1.5 text-sm text-ink-2 transition-colors hover:text-ink">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path d="M12 5l-5 5 5 5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Retour à la boutique
                </a>
            </header>

            <main class="mx-auto my-auto w-full @yield('form_max', 'max-w-[440px]') py-10">
                @include('partials.flash')
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
