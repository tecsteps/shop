@php($currentStore = $currentStore ?? null)
<!doctype html>
<html lang="en" class="bg-white text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? ($currentStore?->name ?? 'Shop') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-950">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-zinc-900 focus:px-4 focus:py-3 focus:text-white">Skip to main content</a>
        <div class="border-b border-zinc-200 bg-zinc-900 px-4 py-2 text-center text-sm text-white dark:border-zinc-800" role="status">
            {{ data_get($currentStore?->settings?->settings_json, 'announcement', 'Free shipping on orders over €50') }}
        </div>
        <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
            <nav class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4 lg:px-8" aria-label="Main navigation">
                <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight" wire:navigate>{{ $currentStore?->name ?? 'Shop' }}</a>
                <div class="hidden items-center gap-6 text-sm font-medium md:flex">
                    <a href="{{ route('collections.index') }}" class="hover:text-blue-600" wire:navigate>Collections</a>
                    <a href="{{ route('collection.show', 'new-arrivals') }}" class="hover:text-blue-600" wire:navigate>New Arrivals</a>
                    <a href="{{ route('page.show', 'about') }}" class="hover:text-blue-600" wire:navigate>About</a>
                    <a href="{{ route('search') }}" class="hover:text-blue-600" wire:navigate>Search</a>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <a href="{{ auth('customer')->check() ? route('account.dashboard') : route('account.login') }}" aria-label="Account" wire:navigate>Account</a>
                    <button type="button" x-data x-on:click="$dispatch('open-cart-drawer')" class="rounded-full bg-zinc-900 px-3 py-2 text-white dark:bg-white dark:text-zinc-900" aria-label="Open cart">Cart</button>
                </div>
            </nav>
        </header>
        <main id="main-content" class="min-h-[60vh]">{{ $slot }}</main>
        <footer class="mt-20 border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
                <div><p class="font-semibold">{{ $currentStore?->name ?? 'Shop' }}</p><p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Thoughtful everyday pieces, made to last.</p></div>
                <div><p class="font-semibold">Shop</p><a class="mt-2 block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white" href="{{ route('collections.index') }}">Collections</a></div>
                <div><p class="font-semibold">Help</p><a class="mt-2 block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white" href="{{ route('account.dashboard') }}">Your account</a></div>
                <div><p class="font-semibold">Stay in the loop</p><p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Subscribe for exclusive offers and updates.</p></div>
            </div>
            <div class="border-t border-zinc-200 px-4 py-5 text-center text-xs text-zinc-500 dark:border-zinc-800">© {{ now()->year }} {{ $currentStore?->name ?? 'Shop' }}. All rights reserved.</div>
        </footer>
        @if ($currentStore)
            <livewire:storefront.cart-drawer />
        @endif
        <livewire:storefront.search.modal />
        @fluxScripts
        @livewireScripts
    </body>
</html>
