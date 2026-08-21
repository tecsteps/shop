@php($currentStore = $currentStore ?? null)
@php($headerCart = $currentStore ? \App\Models\Cart::withoutGlobalScopes()->where('store_id', $currentStore->getKey())->where('status', 'active')->withSum('lines', 'quantity')->find(session('cart_id_'.$currentStore->getKey(), session('cart_id'))) : null)
<!doctype html>
<html lang="{{ $currentStore?->default_locale ?? 'en' }}" class="bg-white text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? ($currentStore?->name ?? 'Shop') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body x-data="{ mobileMenu: false, dark: localStorage.getItem('shop-storefront-dark') === '1' }" x-init="document.documentElement.classList.toggle('dark', dark)" class="min-h-screen bg-white dark:bg-zinc-950">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-zinc-900 focus:px-4 focus:py-3 focus:text-white">Skip to main content</a>
        <div x-data="{ visible: sessionStorage.getItem('shop-announcement-dismissed') !== '1' }" x-show="visible" class="border-b border-zinc-200 bg-zinc-900 px-4 py-2 text-center text-sm text-white dark:border-zinc-800" role="status">
            {{ data_get($currentStore?->settings?->settings_json, 'announcement', 'Free shipping on orders over €50') }}
            <button type="button" class="ml-3 underline" x-on:click="visible = false; sessionStorage.setItem('shop-announcement-dismissed', '1')">Dismiss</button>
        </div>
        <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
            <nav class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4 lg:px-8" aria-label="Main navigation">
                <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight" wire:navigate>{{ data_get($currentStore?->settings?->general_json, 'store_name', $currentStore?->name ?? 'Shop') }}</a>
                <div class="hidden items-center gap-6 text-sm font-medium md:flex">
                    <a href="{{ route('collections.index') }}" class="hover:text-blue-600" wire:navigate>Collections</a>
                    <a href="{{ route('collection.show', 'new-arrivals') }}" class="hover:text-blue-600" wire:navigate>New Arrivals</a>
                    <a href="{{ route('page.show', 'about') }}" class="hover:text-blue-600" wire:navigate>About</a>
                    <button type="button" class="hover:text-blue-600" x-on:click="$dispatch('open-search-modal')">Search</button>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <button type="button" class="rounded-lg border px-2 py-1 md:hidden" x-on:click="mobileMenu = !mobileMenu" aria-label="Toggle navigation" :aria-expanded="mobileMenu.toString()">Menu</button>
                    <a href="{{ auth('customer')->check() ? route('account.dashboard') : route('account.login') }}" aria-label="Account" wire:navigate>Account</a>
                    <button type="button" class="hidden rounded-lg border px-2 py-1 sm:inline-flex" x-on:click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('shop-storefront-dark', dark ? '1' : '0')" x-text="dark ? 'Light' : 'Dark'" aria-label="Toggle dark mode"></button>
                    <button type="button" x-data="{ count: {{ (int) ($headerCart?->lines_sum_quantity ?? 0) }} }" x-on:cart-count-updated.window="count = $event.detail.count" x-on:click="$dispatch('open-cart-drawer')" class="rounded-full bg-zinc-900 px-3 py-2 text-white dark:bg-white dark:text-zinc-900" aria-label="Open cart">Cart <span x-show="count > 0" x-text="`(${count})`" aria-label="items in cart"></span></button>
                </div>
            </nav>
            <div x-show="mobileMenu" x-cloak class="border-t border-zinc-200 px-4 py-4 md:hidden dark:border-zinc-800"><div class="grid gap-3 text-sm font-medium"><a href="{{ route('collections.index') }}" wire:navigate>Collections</a><a href="{{ route('collection.show', 'new-arrivals') }}" wire:navigate>New Arrivals</a><button type="button" class="text-left" x-on:click="$dispatch('open-search-modal'); mobileMenu = false">Search</button></div></div>
        </header>
        <main id="main-content" class="min-h-[60vh]">{{ $slot }}</main>
        <footer class="mt-20 border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
                <div><p class="font-semibold">{{ data_get($currentStore?->settings?->general_json, 'store_name', $currentStore?->name ?? 'Shop') }}</p><p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Thoughtful everyday pieces, made to last.</p></div>
                <div><p class="font-semibold">Shop</p><a class="mt-2 block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white" href="{{ route('collections.index') }}">Collections</a></div>
                <div><p class="font-semibold">Help</p><a class="mt-2 block text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white" href="{{ route('account.dashboard') }}">Your account</a></div>
                <div x-data="{ sent: false }"><p class="font-semibold">Stay in the loop</p><p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Subscribe for exclusive offers and updates.</p><form class="mt-4 flex gap-2" x-on:submit.prevent="sent = true"><label class="sr-only" for="footer-newsletter-email">Email</label><input id="footer-newsletter-email" required type="email" placeholder="Email address" class="min-w-0 w-full rounded-full border border-zinc-300 bg-white px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"><button type="submit" class="rounded-full bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">Join</button></form><p x-show="sent" x-cloak class="mt-2 text-sm text-green-700" role="status">Thanks — you’re on the list.</p></div>
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
