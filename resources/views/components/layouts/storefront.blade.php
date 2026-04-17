@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ? $title.' | '.(isset($currentStore) ? $currentStore->name : config('app.name')) : (isset($currentStore) ? $currentStore->name : config('app.name')) }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="min-h-screen bg-white text-neutral-900 antialiased dark:bg-neutral-950 dark:text-neutral-100">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-neutral-900 focus:px-4 focus:py-2 focus:text-white focus:shadow-lg focus:ring-2 focus:ring-offset-2">
        Skip to main content
    </a>

    <div class="bg-neutral-900 px-4 py-2 text-center text-xs text-white dark:bg-neutral-800">
        Free shipping on orders over $50
    </div>

    <header class="border-b border-neutral-200 bg-white/80 backdrop-blur dark:border-neutral-800 dark:bg-neutral-950/80">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="text-lg font-semibold tracking-tight">
                {{ isset($currentStore) ? $currentStore->name : config('app.name') }}
            </a>

            <nav class="hidden flex-1 justify-center lg:flex" aria-label="Primary">
                <livewire:storefront.navigation handle="main-menu" />
            </nav>

            <div class="flex items-center gap-4">
                <a href="{{ url('/cart') }}" class="text-sm font-medium text-neutral-700 hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-white">
                    Cart
                </a>
                <a href="{{ url('/account/login') }}" class="text-sm font-medium text-neutral-700 hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-white">
                    Sign in
                </a>
            </div>
        </div>
    </header>

    <main id="main-content" class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-8 text-sm text-neutral-600 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8 dark:text-neutral-400">
            <div>
                &copy; {{ now()->year }} {{ isset($currentStore) ? $currentStore->name : config('app.name') }}. All rights reserved.
            </div>
            <nav class="flex flex-wrap gap-4" aria-label="Footer">
                <a href="{{ url('/pages/about') }}" class="hover:text-neutral-900 dark:hover:text-white">About</a>
                <a href="{{ url('/pages/contact') }}" class="hover:text-neutral-900 dark:hover:text-white">Contact</a>
            </nav>
        </div>
    </footer>

    @fluxScripts
    @livewireScripts
</body>
</html>
