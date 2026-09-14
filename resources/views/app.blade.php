<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Titre/description/OG par défaut — chaque page React les précise via
         <Head> (@inertiajs/react), fusionné ici par @inertiaHead. --}}
    <title inertia>{{ config('malkuthar.seo.default_title') }} — {{ config('malkuthar.seo.site_name') }}</title>
    <meta name="description" content="{{ config('malkuthar.seo.default_description') }}">

    @include('partials.tracking-head')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700&family=Fraunces:ital,opsz,wght@1,9..144,400;1,9..144,500&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap">

    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%232e2c28'/><rect x='1' y='1' width='30' height='30' rx='7' fill='none' stroke='%238a6d2c' stroke-width='1'/><text x='16' y='22.5' font-family='Georgia,serif' font-size='17' font-weight='700' fill='%23c8ab5e' text-anchor='middle'>M</text></svg>">
    <meta name="theme-color" content="#f6f4ee">

    @routes
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="min-h-screen bg-ground font-sans text-ink antialiased">
    @include('partials.tracking-body')
    @inertia
</body>
</html>
