<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ ($title ?? '') ? $title . ' - ' . ($currentStore->name ?? config('app.name')) : ($currentStore->name ?? config('app.name')) }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <script>
        if (localStorage.getItem('darkMode') === 'true' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="min-h-screen bg-white text-gray-700 dark:bg-gray-950 dark:text-gray-300 antialiased">
    {{-- Skip link --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[100] focus:bg-white focus:dark:bg-gray-900 focus:px-4 focus:py-2 focus:rounded focus:shadow-lg focus:ring-2 focus:ring-blue-500 focus:text-blue-600 dark:focus:text-blue-400">
        Skip to main content
    </a>

    @php
        $themeSettings = [];
        if (isset($currentStore)) {
            $theme = \App\Models\Theme::where('store_id', $currentStore->id)
                ->where('status', \App\Enums\ThemeStatus::Published)
                ->first();
            $themeSettings = $theme?->settings?->settings_json ?? [];
        }

        $announcementEnabled = $themeSettings['announcement_bar']['enabled'] ?? false;
        $announcementText = $themeSettings['announcement_bar']['text'] ?? '';
        $announcementLink = $themeSettings['announcement_bar']['link'] ?? '';
        $announcementBg = $themeSettings['announcement_bar']['bg_color'] ?? 'bg-gray-900 dark:bg-gray-100';

        $mainMenuItems = [];
        $footerMenuItems = [];
        if (isset($currentStore)) {
            $navService = app(\App\Services\NavigationService::class);
            $mainMenuItems = $navService->getMenuTree('main-menu', $currentStore);
            $footerMenuItems = $navService->getMenuTree('footer-menu', $currentStore);
        }

        $cartItemCount = 0;
        $cartId = session('cart_id');
        if ($cartId) {
            $cartItemCount = \App\Models\CartLine::where('cart_id', $cartId)->sum('quantity');
        }
    @endphp

    {{-- Announcement Bar --}}
    @if($announcementEnabled && $announcementText)
        <div x-data="{ dismissed: localStorage.getItem('announcement_dismissed') === 'true' }"
             x-show="!dismissed"
             x-cloak
             class="{{ $announcementBg }} text-white dark:text-gray-900">
            <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-center relative">
                <p class="text-sm text-center">
                    @if($announcementLink)
                        <a href="{{ $announcementLink }}" class="underline hover:no-underline">{{ $announcementText }}</a>
                    @else
                        {{ $announcementText }}
                    @endif
                </p>
                <button @click="dismissed = true; localStorage.setItem('announcement_dismissed', 'true')"
                        class="absolute right-4 p-1 hover:opacity-70"
                        aria-label="Dismiss announcement">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <header x-data="{ mobileMenuOpen: false }" class="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 sticky top-0 z-50 backdrop-blur">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" aria-label="Main navigation">
            <div class="flex items-center justify-between h-16">
                {{-- Mobile hamburger --}}
                <button @click="mobileMenuOpen = true"
                        class="lg:hidden p-2 -ml-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                        aria-label="Open menu">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                {{-- Logo --}}
                <a href="{{ route('storefront.home') }}" class="text-xl font-bold text-gray-900 dark:text-white lg:flex-none flex-1 text-center lg:text-left">
                    {{ $currentStore->name ?? config('app.name') }}
                </a>

                {{-- Desktop nav --}}
                <div class="hidden lg:flex items-center gap-6 flex-1 justify-center">
                    @foreach($mainMenuItems as $item)
                        <a href="{{ $item['url'] }}"
                           class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>

                {{-- Right icons --}}
                <div class="flex items-center gap-2">
                    {{-- Search --}}
                    <a href="{{ route('storefront.search') }}"
                       class="hidden lg:flex p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                       aria-label="Search">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </a>

                    {{-- Cart --}}
                    <button @click="$dispatch('open-cart-drawer')"
                            class="relative p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                            aria-label="Open cart">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span x-data="{ count: {{ $cartItemCount }} }"
                              @cart-updated.window="count = $event.detail.itemCount ?? count"
                              x-show="count > 0"
                              x-text="count"
                              x-cloak
                              class="absolute -top-0.5 -right-0.5 bg-blue-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                        </span>
                    </button>

                    {{-- Account --}}
                    <a href="{{ auth('customer')->check() ? route('storefront.account.dashboard') : route('storefront.account.login') }}"
                       class="hidden lg:flex p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                       aria-label="{{ auth('customer')->check() ? 'Account' : 'Log in' }}">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </a>
                </div>
            </div>
        </nav>

        {{-- Mobile nav drawer --}}
        <div x-show="mobileMenuOpen"
             x-cloak
             class="fixed inset-0 z-[60] lg:hidden"
             aria-label="Mobile navigation"
             role="dialog"
             aria-modal="true">
            {{-- Backdrop --}}
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="mobileMenuOpen = false"
                 class="fixed inset-0 bg-black/50"></div>

            {{-- Drawer --}}
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="fixed inset-y-0 left-0 w-80 max-w-full bg-white dark:bg-gray-950 shadow-xl flex flex-col">
                <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-800">
                    <span class="text-lg font-bold text-gray-900 dark:text-white">Menu</span>
                    <button @click="mobileMenuOpen = false"
                            class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                            aria-label="Close menu">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-4">
                    <div class="space-y-1">
                        @foreach($mainMenuItems as $item)
                            <a href="{{ $item['url'] }}"
                               class="block px-3 py-3 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 rounded-lg">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-800">
                        <a href="{{ route('storefront.search') }}"
                           class="flex items-center gap-3 px-3 py-3 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            Search
                        </a>
                        <a href="{{ auth('customer')->check() ? route('storefront.account.dashboard') : route('storefront.account.login') }}"
                           class="flex items-center gap-3 px-3 py-3 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ auth('customer')->check() ? 'Account' : 'Log in' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Main content --}}
    <main id="main-content" class="min-h-[calc(100vh-4rem)]">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                @foreach($footerMenuItems as $item)
                    <div>
                        <a href="{{ $item['url'] }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                            {{ $item['label'] }}
                        </a>
                    </div>
                @endforeach

                {{-- Store info --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">{{ $currentStore->name ?? config('app.name') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Your trusted online store.</p>
                </div>
            </div>

            {{-- Social links --}}
            @php
                $socialLinks = $themeSettings['social_links'] ?? [];
            @endphp
            @if(!empty($socialLinks))
                <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-800 flex gap-4">
                    @foreach($socialLinks as $platform => $url)
                        @if($url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                               class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                               aria-label="{{ ucfirst($platform) }}">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Copyright --}}
            <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-gray-400">&copy; {{ date('Y') }} {{ $currentStore->name ?? config('app.name') }}. All rights reserved.</p>
                <div class="flex gap-2 opacity-60">
                    <span class="text-xs text-gray-400 border border-gray-300 dark:border-gray-700 rounded px-2 py-0.5">Visa</span>
                    <span class="text-xs text-gray-400 border border-gray-300 dark:border-gray-700 rounded px-2 py-0.5">Mastercard</span>
                    <span class="text-xs text-gray-400 border border-gray-300 dark:border-gray-700 rounded px-2 py-0.5">PayPal</span>
                </div>
            </div>
        </div>
    </footer>

    {{-- Global Cart Drawer --}}
    <livewire:storefront.cart-drawer />

    @livewireScripts
</body>
</html>
