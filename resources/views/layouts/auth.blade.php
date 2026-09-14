<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-ground font-sans text-ink antialiased">
    @include('partials.tracking-body')

    <div class="grid min-h-screen lg:h-screen lg:grid-cols-[38%_62%] lg:overflow-hidden xl:grid-cols-[34%_66%]">

        {{-- ===================== Panneau latéral (gauche) ===================== --}}
        <aside class="relative hidden overflow-hidden border-r border-line bg-gradient-to-b from-accent-soft/60 via-ground-alt to-ground lg:block">
            {{-- Photo optionnelle : déposez public/images/auth-cover.jpg. --}}
            <img src="{{ asset('images/auth-cover.jpg') }}" alt=""
                 onerror="this.remove()"
                 class="absolute inset-0 h-full w-full object-cover opacity-30 mix-blend-multiply">
            <div class="absolute inset-0 bg-gradient-to-t from-ground/80 via-transparent to-transparent"></div>

            <div class="relative flex h-full flex-col justify-between p-10 xl:p-12">
                <a href="{{ url('/') }}" class="font-display text-[15px] font-semibold tracking-[0.2em] text-ink">MALKUTHAR</a>
                <div>
                    <p class="font-display text-2xl font-semibold leading-snug text-ink">
                        @hasSection('auth_tagline') @yield('auth_tagline') @else L'aube d'un chemin intérieur. @endif
                    </p>
                    <p class="mt-2 text-sm text-ink-2">
                        @hasSection('auth_subtitle') @yield('auth_subtitle') @else Sagesse spirituelle &amp; éveil mystique. @endif
                    </p>
                    @hasSection('auth_panel_extra')
                        <div class="mt-5">@yield('auth_panel_extra')</div>
                    @endif
                </div>
            </div>
        </aside>

        {{-- ===================== Colonne formulaire (droite) ===================== --}}
        <div class="flex min-h-screen flex-col px-5 py-8 sm:px-10 lg:h-screen lg:overflow-y-auto lg:px-14 xl:px-20">
            <header class="flex items-center justify-between">
                <a href="{{ url('/') }}" class="font-display text-[14px] font-bold tracking-[0.2em] text-ink lg:hidden">MALKUTHAR</a>
                <a href="{{ url('/') }}" class="ml-auto text-xs text-ink-3 transition-colors hover:text-ink">← Retour à la boutique</a>
            </header>

            <main class="mx-auto my-auto w-full @yield('form_max', 'max-w-[460px]') py-10">
                @include('partials.flash')
                {{ $slot ?? '' }}
                @yield('content')
            </main>

            <div aria-hidden="true"></div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
