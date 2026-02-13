@php
    $themeSettings = app(\App\Services\ThemeSettingsService::class);
    $store = app()->bound('current_store') ? app('current_store') : null;
    $storeName = $store?->name ?? config('app.name');
    $navigationService = app(\App\Services\NavigationService::class);

    $mainMenu = null;
    $footerMenu = null;
    $mainNavItems = [];
    $footerNavItems = [];

    if ($store) {
        $mainMenu = \App\Models\NavigationMenu::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', 'main-menu')
            ->first();
        $footerMenu = \App\Models\NavigationMenu::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', 'footer-menu')
            ->first();

        if ($mainMenu) {
            $mainMenu->load('items');
            $mainNavItems = $navigationService->buildTree($mainMenu);
        }
        if ($footerMenu) {
            $footerMenu->load('items');
            $footerNavItems = $navigationService->buildTree($footerMenu);
        }
    }

    $announcementBar = $themeSettings->get('announcement_bar', []);
    $stickyHeader = $themeSettings->get('sticky_header', false);
    $logoUrl = $themeSettings->get('logo_url');
    $socialLinks = $themeSettings->get('social_links', []);
    $darkMode = $themeSettings->get('dark_mode', 'system');
    $footerSettings = $themeSettings->get('footer', []);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if($darkMode === 'dark') class="dark" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $metaDescription ?? '' }}">
    <title>{{ isset($title) ? $title . ' - ' . $storeName : $storeName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>html { scroll-behavior: smooth; }</style>
</head>
<body class="min-h-screen bg-white text-zinc-700 dark:bg-zinc-950 dark:text-zinc-300 antialiased">
    {{-- Skip link --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[100] focus:bg-white focus:text-zinc-900 focus:px-4 focus:py-2 focus:rounded-md focus:shadow-lg focus:ring-2 focus:ring-blue-500 dark:focus:bg-zinc-800 dark:focus:text-white">
        Skip to main content
    </a>

    {{-- Announcement Bar --}}
    @if(!empty($announcementBar['enabled']) && !empty($announcementBar['text']))
        <div x-data="{ dismissed: localStorage.getItem('announcement_dismissed') === 'true' }"
             x-show="!dismissed"
             x-cloak
             class="relative bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900"
             style="{{ !empty($announcementBar['background_color']) ? 'background-color: ' . $announcementBar['background_color'] : '' }}">
            <div class="mx-auto max-w-7xl px-4 py-2 text-center text-sm">
                @if(!empty($announcementBar['link']))
                    <a href="{{ $announcementBar['link'] }}" class="underline hover:no-underline">
                        {{ $announcementBar['text'] }}
                    </a>
                @else
                    {{ $announcementBar['text'] }}
                @endif
            </div>
            <button @click="dismissed = true; localStorage.setItem('announcement_dismissed', 'true')"
                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-white/80 hover:text-white dark:text-zinc-600 dark:hover:text-zinc-900"
                    aria-label="Dismiss announcement">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Header --}}
    <header class="{{ $stickyHeader ? 'sticky top-0 z-50 backdrop-blur-md bg-white/90 dark:bg-zinc-950/90 border-b border-transparent scroll-border' : 'relative bg-white dark:bg-zinc-950' }} border-b border-zinc-200 dark:border-zinc-800"
            x-data="{ mobileMenuOpen: false }">
        <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" aria-label="Main navigation">
            <div class="flex h-16 items-center justify-between">
                {{-- Mobile: Hamburger --}}
                <div class="flex lg:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                            type="button"
                            class="inline-flex items-center justify-center rounded-md p-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                            aria-label="Open mobile navigation"
                            :aria-expanded="mobileMenuOpen">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                </div>

                {{-- Logo --}}
                <div class="flex flex-1 items-center justify-center lg:justify-start">
                    <a href="{{ route('storefront.home') }}" class="flex items-center">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="h-8 w-auto lg:h-10">
                        @else
                            <span class="text-xl font-bold text-zinc-900 dark:text-white">{{ $storeName }}</span>
                        @endif
                    </a>
                </div>

                {{-- Desktop Navigation --}}
                <div class="hidden lg:flex lg:items-center lg:space-x-8">
                    @foreach($mainNavItems as $item)
                        <a href="{{ $item['url'] }}"
                           class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white transition-colors">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>

                {{-- Right Icons --}}
                <div class="flex items-center space-x-4">
                    {{-- Search --}}
                    <button type="button"
                            class="hidden lg:inline-flex items-center justify-center p-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                            aria-label="Search"
                            @click="$dispatch('open-search-modal')">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </button>

                    {{-- Cart --}}
                    <button type="button"
                            class="relative inline-flex items-center justify-center p-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                            aria-label="Open cart"
                            @click="$dispatch('open-cart-drawer')">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </button>

                    {{-- Account --}}
                    <a href="{{ auth('customer')->check() ? route('customer.dashboard') : route('customer.login') }}"
                       class="hidden lg:inline-flex items-center justify-center p-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                       aria-label="{{ auth('customer')->check() ? 'Account' : 'Log in' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </a>
                </div>
            </div>
        </nav>

        {{-- Mobile Navigation Drawer --}}
        <div x-show="mobileMenuOpen"
             x-cloak
             class="fixed inset-0 z-50 lg:hidden"
             role="dialog"
             aria-modal="true"
             aria-label="Mobile navigation">
            {{-- Backdrop --}}
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition-opacity ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="mobileMenuOpen = false"
                 class="fixed inset-0 bg-black/50"></div>

            {{-- Drawer Panel --}}
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="fixed inset-y-0 left-0 w-full max-w-xs bg-white dark:bg-zinc-900 shadow-xl"
                 @keydown.escape.window="mobileMenuOpen = false">
                <div class="flex h-full flex-col">
                    {{-- Close button --}}
                    <div class="flex items-center justify-end p-4">
                        <button @click="mobileMenuOpen = false"
                                class="rounded-md p-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                                aria-label="Close mobile navigation">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Nav Items --}}
                    <nav class="flex-1 space-y-1 px-4 pb-4">
                        @foreach($mainNavItems as $item)
                            <a href="{{ $item['url'] }}"
                               class="block rounded-md px-3 py-3 text-base font-medium text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    {{-- Account link at bottom --}}
                    <div class="border-t border-zinc-200 dark:border-zinc-700 p-4">
                        <a href="{{ auth('customer')->check() ? route('customer.dashboard') : route('customer.login') }}"
                           class="flex items-center space-x-3 rounded-md px-3 py-3 text-base font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            <span>{{ auth('customer')->check() ? 'My Account' : 'Log in' }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main id="main-content" class="min-h-[calc(100vh-4rem)]">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900" role="contentinfo">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            {{-- Footer columns --}}
            <div class="grid grid-cols-2 gap-8 md:grid-cols-3 lg:grid-cols-4">
                {{-- Navigation links --}}
                @if(count($footerNavItems) > 0)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Navigation</h3>
                        <ul class="mt-4 space-y-3">
                            @foreach($footerNavItems as $item)
                                <li>
                                    <a href="{{ $item['url'] }}"
                                       class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white transition-colors">
                                        {{ $item['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Store Info --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $storeName }}</h3>
                    <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        @if(!empty($footerSettings['store_address']))
                            <p>{{ $footerSettings['store_address'] }}</p>
                        @endif
                        @if(!empty($footerSettings['contact_email']))
                            <p>
                                <a href="mailto:{{ $footerSettings['contact_email'] }}" class="hover:text-zinc-900 dark:hover:text-white transition-colors">
                                    {{ $footerSettings['contact_email'] }}
                                </a>
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Social Links --}}
            @if(array_filter($socialLinks))
                <div class="mt-8 flex space-x-4">
                    @foreach(['facebook', 'instagram', 'twitter', 'tiktok', 'youtube'] as $platform)
                        @if(!empty($socialLinks[$platform]))
                            <a href="{{ $socialLinks[$platform] }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition-colors"
                               aria-label="{{ ucfirst($platform) }}">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <rect width="24" height="24" rx="4" fill="currentColor" opacity="0.15"/>
                                    <text x="12" y="16" text-anchor="middle" font-size="10" fill="currentColor">{{ strtoupper(substr($platform, 0, 1)) }}</text>
                                </svg>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Copyright --}}
            <div class="mt-8 border-t border-zinc-200 dark:border-zinc-800 pt-8">
                <p class="text-xs text-zinc-400 dark:text-zinc-500">
                    &copy; {{ date('Y') }} {{ $storeName }}. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    {{-- Search Modal --}}
    @livewire('storefront.search.modal')

    {{-- Cart Drawer --}}
    @livewire('storefront.cart-drawer')

    @livewireScripts
</body>
</html>
