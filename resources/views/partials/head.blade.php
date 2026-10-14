<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>@yield('title', config('malkuthar.seo.default_title')) — MALKUTHAR</title>

@include('partials.seo')
@include('partials.tracking-head')

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=JetBrains+Mono:wght@400;500&display=swap">

<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%231c1917'/><rect x='1' y='1' width='30' height='30' rx='7' fill='none' stroke='%238a6d2c' stroke-width='1'/><text x='16' y='22.5' font-family='Georgia,serif' font-size='17' font-weight='700' fill='%23c8ab5e' text-anchor='middle'>M</text></svg>">
<meta name="theme-color" content="#f7f5f0">

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Alpine.js — v1 via CDN. À bundler dans resources/js/app.js en v2
     (npm i alpinejs). --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>

@stack('head')
