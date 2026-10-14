<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="theme-night min-h-screen bg-night font-sans text-night-ink antialiased">
    <x-layout.navbar-admin />

    @include('partials.flash')

    <div class="mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:flex lg:gap-10 lg:py-8">
        <x-layout.sidebar-admin />

        <main id="contenu" class="min-w-0 flex-1 pb-16">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                <div>
                    @hasSection('eyebrow')
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-accent-light">@yield('eyebrow')</p>
                    @endif
                    <h1 class="mt-1 font-display text-3xl font-semibold text-night-ink sm:text-4xl">@yield('heading', 'Administration')</h1>
                </div>
                @hasSection('actions')
                    <div class="flex flex-wrap gap-2">@yield('actions')</div>
                @endif
            </div>

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
