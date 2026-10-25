<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="theme-dashboard min-h-screen bg-gray-100 font-sans text-ink antialiased">
    @include('partials.tracking-body')

    <x-layout.navbar-app />

    @include('partials.flash')

    <div class="mx-auto max-w-[1320px] px-4 py-6 sm:px-6 lg:flex lg:gap-10 lg:py-10">
        @yield('sidebar')

        <main id="contenu" class="min-w-0 flex-1">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    <x-layout.bottom-nav />

    @stack('scripts')
</body>
</html>
