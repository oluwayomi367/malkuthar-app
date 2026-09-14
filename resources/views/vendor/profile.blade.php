@extends('layouts.app')

@section('title', 'Ma boutique')
@section('sidebar')<x-layout.sidebar-app space="vendor" />@endsection

@section('content')
<p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-accent">Espace vendeur</p>
<h1 class="mt-1 font-display text-2xl font-bold tracking-[-0.01em] text-ink sm:text-3xl">Ma boutique</h1>
<p class="mt-1 text-sm text-ink-2">L'identité publique vue par les acheteurs sur votre page vendeur.</p>

<form method="POST" action="{{ route('vendor.profile.update') }}" enctype="multipart/form-data" class="mt-6 grid max-w-2xl gap-4">
    @csrf
    @method('PUT')

    <x-ui.field name="display_name" label="Nom de la boutique" :required="true" :value="$profile->display_name" />
    <x-ui.textarea name="bio" label="Présentation" :rows="4" :required="true">{{ $profile->bio }}</x-ui.textarea>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="region" label="Région" :required="true" :value="$profile->region" />
        <x-ui.field name="city" label="Ville" :value="$profile->city" />
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field name="contact_email" label="Email de contact" type="email" :value="$profile->contact_email" />
        <x-ui.field name="phone" label="Téléphone" :value="$profile->phone" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="text-[13px] font-medium text-ink-2">Logo</label>
            @if ($profile->logo_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($profile->logo_path) }}" alt="" class="mt-1 h-16 w-16 rounded-xl object-cover">
            @endif
            <input type="file" name="logo" accept="image/*" class="mt-1 block w-full text-sm text-ink-2 file:mr-3 file:rounded-lg file:border-0 file:bg-ground-alt file:px-3 file:py-2 file:text-sm">
            @error('logo')<p class="text-xs text-rust">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-[13px] font-medium text-ink-2">Bannière</label>
            @if ($profile->banner_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($profile->banner_path) }}" alt="" class="mt-1 h-16 w-full rounded-xl object-cover">
            @endif
            <input type="file" name="banner" accept="image/*" class="mt-1 block w-full text-sm text-ink-2 file:mr-3 file:rounded-lg file:border-0 file:bg-ground-alt file:px-3 file:py-2 file:text-sm">
            @error('banner')<p class="text-xs text-rust">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-2">
        <x-ui.button type="submit" variant="primary">Enregistrer</x-ui.button>
    </div>
</form>
@endsection
