@php
    /** @var \App\Services\ThemeSettingsService $themeSettingsService */
    $themeSettingsService = app(\App\Services\ThemeSettingsService::class);
    $currentStore = app()->bound('current_store') ? app('current_store') : \App\Models\Store::first();
    $themeSettings = $currentStore ? $themeSettingsService->forStore($currentStore) : $themeSettingsService->defaultSettings();
    $announcement = $themeSettings['announcement'] ?? null;
    $footerText = $themeSettings['footer_text'] ?? '(c) Shop';
    $primaryColor = $themeSettings['colors']['primary'] ?? '#111';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title>{{ $title ?? ($currentStore->name ?? config('app.name')) }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @vite(['resources/css/app.css'])
        @livewireStyles
        @fluxAppearance
        <style>
            :root { --storefront-accent: {{ $primaryColor }}; }
        </style>
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        @include('storefront.partials.announcement', ['announcement' => $announcement])
        @include('storefront.partials.header', ['store' => $currentStore])

        <main class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>

        @include('storefront.partials.footer', ['footerText' => $footerText])

        {{-- Cart drawer placeholder for Phase 4 --}}
        <div id="cart-drawer-slot"></div>

        @livewireScripts
        @fluxScripts
    </body>
</html>
