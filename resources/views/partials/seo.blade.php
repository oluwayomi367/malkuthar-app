{{-- §74 — méta SEO + Open Graph. Les vues fournissent $seoTitle /
    $seoDescription / $seoImage via @section ou View::share. --}}
@php
    $seoTitle = trim($__env->yieldContent('title')) ?: config('malkuthar.seo.default_title');
    $seoTitle = $seoTitle === '' ? config('malkuthar.seo.default_title') : $seoTitle.' — '.config('malkuthar.seo.site_name');
    $seoDescription = trim($__env->yieldContent('seo_description')) ?: config('malkuthar.seo.default_description');
    $seoUrl = url()->current();
@endphp
<meta name="description" content="{{ $seoDescription }}">
<link rel="canonical" href="{{ $seoUrl }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('malkuthar.seo.site_name') }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoUrl }}">
<meta name="twitter:card" content="summary_large_image">
