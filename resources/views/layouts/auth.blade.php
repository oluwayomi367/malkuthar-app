<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="flex min-h-screen flex-col bg-ground font-sans text-ink antialiased">
    @include('partials.tracking-body')

    <x-layout.navbar-public />

    {{-- Formulaire centré dans une carte blanche, photo arrondie à droite (desktop). --}}
    <main id="contenu" class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-6xl flex-col items-center justify-center gap-10 lg:flex-row lg:items-start lg:gap-16 xl:gap-20">
            <div class="flex w-full @yield('form_max', 'max-w-[440px]') flex-col items-center">
                @hasSection('auth_heading')
                    <h1 class="text-center font-display text-[2.1rem] font-normal leading-tight tracking-[-0.03em] text-ink sm:text-[2.6rem]">@yield('auth_heading')</h1>
                @endif
                @hasSection('auth_intro')
                    <p class="mt-2 max-w-md text-center text-sm text-ink-2 sm:text-[15px]">@yield('auth_intro')</p>
                @endif

                <div class="mt-7 w-full rounded-[28px] border border-line bg-surface p-6 shadow-[0_4px_30px_rgba(0,0,0,0.04)] sm:p-8">
                    @include('partials.flash')
                    {{ $slot ?? '' }}
                    @yield('content')
                </div>

                @hasSection('auth_footer')
                    <div class="mt-6 flex flex-col items-center gap-3 text-center">@yield('auth_footer')</div>
                @endif

                <nav aria-label="Liens utiles" class="mt-5 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-xs text-ink-3">
                    <a href="{{ url('/') }}" class="hover:text-ink">← Accueil</a>
                    <span aria-hidden="true">•</span>
                    <a href="{{ route('catalog.index') }}" class="hover:text-ink">Boutique</a>
                    <span aria-hidden="true">•</span>
                    <a href="{{ route('affiliation') }}" class="hover:text-ink">Affiliation</a>
                </nav>
            </div>

            <div class="hidden w-full max-w-[440px] lg:sticky lg:top-12 lg:block">
                <figure class="relative aspect-[4/5] w-full overflow-hidden rounded-[32px] border border-ink/5 bg-ground-alt shadow-sm">
                    <img src="{{ \App\Support\StorefrontImages::url(trim($__env->yieldContent('auth_image', 'auth.login'))) }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                    @hasSection('auth_tagline')
                        <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-deep/90 via-deep/50 to-transparent p-7 pt-20 text-white">
                            <p class="font-display text-[24px] font-medium leading-tight">@yield('auth_tagline')</p>
                            @hasSection('auth_subtitle')
                                <p class="mt-2 text-sm leading-relaxed text-white/80">@yield('auth_subtitle')</p>
                            @endif
                        </figcaption>
                    @endif
                </figure>
            </div>
        </div>
    </main>

    <x-layout.footer />

    @stack('scripts')
</body>
</html>
