@props(['title' => null])

@php
    $store = isset($currentStore) ? $currentStore : null;
    $storeName = $store ? $store->name : config('app.name');

    $announcement = ['enabled' => true, 'text' => 'Free shipping on orders over $50', 'link' => null];
    if ($store) {
        $settings = $store->settings;
        $json = is_array($settings?->settings_json ?? null) ? $settings->settings_json : [];
        if (isset($json['announcement']) && is_array($json['announcement'])) {
            $announcement = array_merge($announcement, $json['announcement']);
        }
    }

    $currentPath = '/'.ltrim(request()->path(), '/');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ? $title.' | '.$storeName : $storeName }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="min-h-screen bg-white text-neutral-900 antialiased dark:bg-neutral-950 dark:text-neutral-100">
    <a href="#main-content"
        class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-neutral-900 focus:px-4 focus:py-2 focus:font-semibold focus:text-white focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 dark:focus:bg-white dark:focus:text-neutral-900 dark:focus:ring-neutral-900">
        Skip to main content
    </a>

    @if (! empty($announcement['enabled']) && ! empty($announcement['text']))
        <div role="region" aria-label="Announcement"
            class="bg-neutral-900 px-4 py-2 text-center text-xs text-white dark:bg-neutral-800">
            @if (! empty($announcement['link']))
                <a href="{{ $announcement['link'] }}" class="underline underline-offset-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                    {{ $announcement['text'] }}
                </a>
            @else
                {{ $announcement['text'] }}
            @endif
        </div>
    @endif

    <header class="border-b border-neutral-200 bg-white/80 backdrop-blur dark:border-neutral-800 dark:bg-neutral-950/80">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}"
                @class([
                    'text-lg font-semibold tracking-tight rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 dark:focus-visible:ring-white',
                ])
                @if ($currentPath === '/') aria-current="page" @endif>
                {{ $storeName }}
            </a>

            <nav class="hidden flex-1 justify-center lg:flex" aria-label="Primary">
                <livewire:storefront.navigation handle="main-menu" />
            </nav>

            <div class="flex items-center gap-4">
                <livewire:storefront.cart.drawer />
                @auth('customer')
                    <a href="{{ url('/account') }}"
                        class="rounded text-sm font-medium text-neutral-700 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 dark:text-neutral-300 dark:hover:text-white dark:focus-visible:ring-white">
                        Account
                    </a>
                @else
                    <a href="{{ url('/account/login') }}"
                        class="rounded text-sm font-medium text-neutral-700 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 dark:text-neutral-300 dark:hover:text-white dark:focus-visible:ring-white">
                        Sign in
                    </a>
                @endauth
            </div>
        </div>
    </header>

    @if (session('status') || session('success') || session('error'))
        <div aria-live="polite" role="status"
            class="mx-auto mt-4 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status') || session('success'))
                <div class="rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                    {{ session('status') ?? session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    {{ session('error') }}
                </div>
            @endif
        </div>
    @endif

    <main id="main-content" role="main" class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <footer role="contentinfo" class="mt-16 border-t border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-8 text-sm text-neutral-600 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8 dark:text-neutral-400">
            <div>
                &copy; {{ now()->year }} {{ $storeName }}. All rights reserved.
            </div>
            <nav class="flex flex-wrap gap-4" aria-label="Footer">
                <a href="{{ url('/pages/about') }}" class="rounded hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 dark:hover:text-white dark:focus-visible:ring-white">About</a>
                <a href="{{ url('/pages/contact') }}" class="rounded hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 dark:hover:text-white dark:focus-visible:ring-white">Contact</a>
            </nav>
        </div>
    </footer>

    @fluxScripts
    @livewireScripts
</body>
</html>
