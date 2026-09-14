<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-ground font-sans text-ink antialiased">
    @include('partials.tracking-body')

    <x-layout.navbar-public />

    @include('partials.flash')

    <main>
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    @hasSection('footer')
        @yield('footer')
    @else
        <x-layout.footer />
    @endif

    @stack('scripts')
</body>
</html>
