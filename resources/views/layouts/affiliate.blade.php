<!DOCTYPE html>
<html lang="fr" class="bg-ink">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Mon espace affilié') — MALKUTHAR</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    {{-- Assume Tailwind compilé via Vite avec les tokens de
    tailwind.config.snippet.js fusionnés dans le projet. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Alpine.js pour les micro-interactions (copier le lien). En
    production, préférer l'installer via npm et l'importer dans app.js
    plutôt que ce CDN. --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-variant-numeric: tabular-nums; }
    </style>
</head>
<body class="min-h-screen bg-ink font-sans text-paper antialiased" style="padding-bottom: env(safe-area-inset-bottom);">
    @yield('content')
</body>
</html>
