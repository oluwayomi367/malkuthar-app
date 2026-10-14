@extends('layouts.app')

@section('title', 'Ma boutique')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
    $fileCls = 'block w-full text-sm text-ink-2 file:mr-3 file:min-h-11 file:cursor-pointer file:rounded-full file:border file:border-line-strong file:bg-surface file:px-5 file:text-sm file:font-semibold file:text-ink hover:file:border-ink';
@endphp

<header class="flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        <h1 class="font-display text-[32px] font-semibold leading-tight text-ink sm:text-[40px]">Ma boutique</h1>
        <p class="mt-2 text-[15px] text-ink-2">L'identité publique vue par les acheteurs sur votre page vendeur.</p>
    </div>
    @if ($profile->slug)
        <a href="{{ route('marketplace.shop.vendor', $profile->slug) }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-ink underline decoration-line-strong underline-offset-4 hover:decoration-ink">
            Voir ma page publique
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
    @endif
</header>

<form method="POST" action="{{ route('vendor.profile.update') }}" enctype="multipart/form-data" class="mt-8 max-w-3xl space-y-6">
    @csrf
    @method('PUT')

    {{-- Identité --}}
    <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="identity-title">
        <h2 id="identity-title" class="font-sans text-lg font-semibold text-ink">Présentation</h2>
        <div class="mt-4 grid gap-5">
            <x-ui.field name="display_name" label="Nom de la boutique" :required="true" :value="$profile->display_name" />
            <x-ui.textarea name="bio" label="Présentation" :rows="5" :required="true"
                           hint="Ce que vous proposez, votre approche, votre expérience.">{{ $profile->bio }}</x-ui.textarea>
        </div>
    </section>

    {{-- Coordonnées --}}
    <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="contact-title">
        <h2 id="contact-title" class="font-sans text-lg font-semibold text-ink">Localisation et contact</h2>
        <div class="mt-4 grid gap-5 sm:grid-cols-2">
            <x-ui.field name="region" label="Région" :required="true" :value="$profile->region" />
            <x-ui.field name="city" label="Ville" :value="$profile->city" />
            <x-ui.field name="contact_email" label="Email de contact" type="email" :value="$profile->contact_email" />
            <x-ui.field name="phone" label="Téléphone" :value="$profile->phone" />
        </div>
    </section>

    {{-- Visuels --}}
    <section class="rounded-2xl border border-line bg-surface p-5 sm:p-6" aria-labelledby="visuals-title">
        <h2 id="visuals-title" class="font-sans text-lg font-semibold text-ink">Logo et bannière</h2>
        <p class="mt-1 text-sm text-ink-2">Laissez vide pour conserver les images actuelles.</p>

        {{-- Aperçu --}}
        <div class="mt-4 overflow-hidden rounded-xl border border-line bg-ground-alt">
            <div class="h-28 w-full sm:h-36">
                @if ($profile->banner_path)
                    <img src="{{ Storage::url($profile->banner_path) }}" alt="Bannière actuelle" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full items-center justify-center text-[13px] text-ink-3">Aucune bannière</div>
                @endif
            </div>
            <div class="flex items-center gap-3 border-t border-line bg-surface px-4 py-3">
                <span class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border border-line bg-ground-alt">
                    @if ($profile->logo_path)
                        <img src="{{ Storage::url($profile->logo_path) }}" alt="Logo actuel" class="h-full w-full object-cover">
                    @endif
                </span>
                <span class="min-w-0">
                    <span class="block truncate font-semibold text-ink">{{ $profile->display_name }}</span>
                    <span class="block text-[13px] text-ink-3">Aperçu de l'en-tête de votre page</span>
                </span>
            </div>
        </div>

        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <div class="flex flex-col gap-1.5">
                <label for="logo" class="text-sm font-semibold text-ink">Logo</label>
                <input id="logo" type="file" name="logo" accept="image/*" class="{{ $fileCls }}"
                       @error('logo') aria-invalid="true" aria-describedby="logo-error" @enderror>
                @error('logo')<p id="logo-error" class="text-[13px] font-medium text-rust">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="banner" class="text-sm font-semibold text-ink">Bannière</label>
                <input id="banner" type="file" name="banner" accept="image/*" class="{{ $fileCls }}"
                       @error('banner') aria-invalid="true" aria-describedby="banner-error" @enderror>
                @error('banner')<p id="banner-error" class="text-[13px] font-medium text-rust">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <div>
        <button type="submit" class="inline-flex min-h-11 items-center rounded-full bg-space-vendor px-6 text-sm font-semibold text-white transition-opacity hover:opacity-90">Enregistrer</button>
    </div>
</form>
@endsection
