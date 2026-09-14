@if (session('status') || session('success') || session('error'))
    <div class="mx-auto w-full max-w-2xl px-4 pt-4">
        @if (session('error'))
            <x-ui.alert tone="error">{{ session('error') }}</x-ui.alert>
        @elseif (session('success'))
            <x-ui.alert tone="success">{{ session('success') }}</x-ui.alert>
        @else
            <x-ui.alert tone="info">{{ session('status') }}</x-ui.alert>
        @endif
    </div>
@endif
