<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        @if(isset($metaDescription))
            <meta name="description" content="{{ $metaDescription }}">
        @endif

        <title>{{ $title ?? (app()->bound('current_store') ? app('current_store')->name : config('app.name')) }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-white text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
        {{-- Skip Link --}}
        <a href="#main-content"
           class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-white focus:px-4 focus:py-2 focus:shadow-lg focus:ring-2 focus:ring-blue-500 dark:focus:bg-gray-900">
            Skip to main content
        </a>

        {{-- Header --}}
        @php
            $store = app()->bound('current_store') ? app('current_store') : null;
            $storeName = $store?->name ?? config('app.name');
        @endphp
        <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-950/95">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                {{-- Mobile: Hamburger --}}
                <button
                    x-data
                    @click="$dispatch('toggle-mobile-nav')"
                    class="inline-flex items-center justify-center rounded p-2 text-gray-600 hover:text-gray-900 lg:hidden dark:text-gray-400 dark:hover:text-gray-100"
                    aria-label="Open navigation"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                </button>

                {{-- Logo --}}
                <a href="{{ route('storefront.home') }}" class="flex items-center text-xl font-bold tracking-tight">
                    {{ $storeName }}
                </a>

                {{-- Desktop Navigation --}}
                <nav class="hidden items-center gap-6 lg:flex" aria-label="Main navigation">
                    <a href="{{ url('/collections') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Collections</a>
                    <a href="{{ url('/products') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Products</a>
                </nav>

                {{-- Right icons --}}
                <div class="flex items-center gap-3">
                    {{-- Search --}}
                    <a href="{{ url('/search') }}" class="inline-flex items-center justify-center rounded p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100" aria-label="Search">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    </a>

                    {{-- Cart --}}
                    <a href="{{ url('/cart') }}" class="relative inline-flex items-center justify-center rounded p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100" aria-label="Cart">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    </a>

                    {{-- Account --}}
                    <a href="{{ url('/account/login') }}" class="inline-flex items-center justify-center rounded p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100" aria-label="Account">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                    </a>
                </div>
            </div>
        </header>

        {{-- Mobile Navigation Drawer --}}
        <div
            x-data="{ open: false }"
            @toggle-mobile-nav.window="open = !open"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 lg:hidden"
        >
            <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 bg-black/50"></div>
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="fixed inset-y-0 left-0 w-72 bg-white p-6 shadow-xl dark:bg-gray-900"
                role="dialog"
                aria-label="Mobile navigation"
            >
                <button @click="open = false" class="absolute top-4 right-4 p-2 text-gray-500 hover:text-gray-900 dark:hover:text-white" aria-label="Close navigation">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
                <nav class="mt-8 flex flex-col gap-4" aria-label="Mobile navigation">
                    <a href="{{ route('storefront.home') }}" class="text-base font-medium text-gray-900 dark:text-white">Home</a>
                    <a href="{{ url('/collections') }}" class="text-base font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Collections</a>
                    <a href="{{ url('/products') }}" class="text-base font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Products</a>
                    <a href="{{ url('/account/login') }}" class="mt-auto text-base font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Account</a>
                </nav>
            </div>
        </div>

        {{-- Main Content --}}
        <main id="main-content" class="flex-1">
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    {{-- Shop links --}}
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Shop</h3>
                        <ul class="mt-4 space-y-3">
                            <li><a href="{{ url('/collections') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Collections</a></li>
                            <li><a href="{{ url('/search') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Search</a></li>
                        </ul>
                    </div>

                    {{-- Help links --}}
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Help</h3>
                        <ul class="mt-4 space-y-3">
                            <li><a href="{{ url('/pages/about') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">About</a></li>
                            <li><a href="{{ url('/pages/contact') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Contact</a></li>
                        </ul>
                    </div>

                    {{-- Store info --}}
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $storeName }}</h3>
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Quality products, delivered to your door.</p>
                    </div>
                </div>

                <div class="mt-12 border-t border-gray-200 pt-8 dark:border-gray-800">
                    <p class="text-center text-xs text-gray-500 dark:text-gray-400">&copy; {{ date('Y') }} {{ $storeName }}. All rights reserved.</p>
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
