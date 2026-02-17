<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $metaDescription ?? '' }}">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white text-gray-700 antialiased dark:bg-gray-950 dark:text-gray-300">
    {{-- Skip link --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-gray-900 focus:shadow-lg focus:ring-2 focus:ring-blue-500 dark:focus:bg-gray-900 dark:focus:text-white">
        Skip to main content
    </a>

    @php
        $themeSettings = app(\App\Services\ThemeSettingsService::class);
        $navigationService = app(\App\Services\NavigationService::class);
        $announcementBar = $themeSettings->get('announcement_bar', []);
        $stickyHeader = $themeSettings->get('sticky_header', false);
    @endphp

    {{-- Announcement bar --}}
    @if(!empty($announcementBar['enabled']) && !empty($announcementBar['text']))
        <div x-data="{ dismissed: localStorage.getItem('announcement_dismissed') === 'true' }"
             x-show="!dismissed"
             x-cloak
             class="relative bg-gray-900 px-4 py-2.5 text-center text-sm text-white dark:bg-gray-100 dark:text-gray-900">
            @if(!empty($announcementBar['link']))
                <a href="{{ $announcementBar['link'] }}" class="underline hover:no-underline">
                    {{ $announcementBar['text'] }}
                </a>
            @else
                <span>{{ $announcementBar['text'] }}</span>
            @endif
            <button @click="dismissed = true; localStorage.setItem('announcement_dismissed', 'true')"
                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-white/70 hover:text-white dark:text-gray-600 dark:hover:text-gray-900"
                    aria-label="Dismiss announcement">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Header --}}
    <header class="{{ $stickyHeader ? 'sticky top-0 z-40 backdrop-blur-md bg-white/90 dark:bg-gray-950/90 border-b border-transparent [&.scrolled]:border-gray-200 dark:[&.scrolled]:border-gray-800' : '' }} border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950"
            role="banner"
            @if($stickyHeader) x-data x-on:scroll.window="$el.classList.toggle('scrolled', window.scrollY > 10)" @endif>
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            {{-- Mobile hamburger --}}
            <button x-data x-on:click="$dispatch('toggle-mobile-nav')"
                    class="rounded-md p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 lg:hidden dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                    aria-label="Open navigation menu">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            {{-- Logo --}}
            <a href="/" class="flex-shrink-0 text-xl font-bold text-gray-900 dark:text-white">
                @if(app()->bound('current_store'))
                    {{ app('current_store')->name }}
                @else
                    {{ config('app.name') }}
                @endif
            </a>

            {{-- Desktop navigation --}}
            @php
                $mainMenuItems = [];
                if (app()->bound('current_store')) {
                    $mainMenu = \App\Models\NavigationMenu::withoutGlobalScopes()
                        ->where('store_id', app('current_store')->id)
                        ->where('handle', 'main-menu')
                        ->first();
                    if ($mainMenu) {
                        $mainMenuItems = $navigationService->buildTree($mainMenu);
                    }
                }
            @endphp
            <nav class="hidden lg:flex lg:items-center lg:gap-x-8" aria-label="Main navigation">
                @foreach($mainMenuItems as $item)
                    <a href="{{ $item['url'] }}"
                       class="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- Right icons --}}
            <div class="flex items-center gap-x-3">
                {{-- Search --}}
                <button x-data @click="$dispatch('open-search')"
                        class="hidden rounded-md p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 lg:block dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                        aria-label="Search">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </button>

                {{-- Cart --}}
                <a href="/cart"
                   class="relative rounded-md p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                   aria-label="Shopping cart">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </a>

                {{-- Account --}}
                @if(auth()->guard('customer')->check())
                    <a href="/account"
                       class="hidden rounded-md p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 lg:block dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                       aria-label="Your account">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </a>
                @else
                    <a href="{{ route('storefront.login') }}"
                       class="hidden rounded-md p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 lg:block dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                       aria-label="Log in">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </a>
                @endif
            </div>
        </div>
    </header>

    {{-- Mobile navigation drawer --}}
    <div x-data="{ open: false }"
         x-on:toggle-mobile-nav.window="open = !open"
         x-cloak>
        {{-- Backdrop --}}
        <div x-show="open"
             x-transition:enter="transition-opacity duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="open = false"
             class="fixed inset-0 z-40 bg-black/50"></div>

        {{-- Drawer --}}
        <div x-show="open"
             x-transition:enter="transition-transform duration-300"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition-transform duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             @keydown.escape.window="open = false"
             class="fixed inset-y-0 left-0 z-50 w-80 max-w-[85vw] bg-white shadow-xl dark:bg-gray-950"
             role="dialog"
             aria-modal="true"
             aria-label="Mobile navigation">
            <div class="flex h-full flex-col">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                    <span class="text-lg font-semibold text-gray-900 dark:text-white">Menu</span>
                    <button @click="open = false"
                            class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                            aria-label="Close navigation">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <nav class="flex-1 overflow-y-auto px-4 py-4" aria-label="Mobile navigation">
                    @foreach($mainMenuItems as $item)
                        <a href="{{ $item['url'] }}"
                           class="block rounded-md px-3 py-3 text-base font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
                <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-800">
                    @if(auth()->guard('customer')->check())
                        <a href="/account"
                           class="block rounded-md px-3 py-3 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white">
                            My Account
                        </a>
                    @else
                        <a href="{{ route('storefront.login') }}"
                           class="block rounded-md px-3 py-3 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white">
                            Log in
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Search modal --}}
    @livewire('storefront.search.modal')

    {{-- Main content --}}
    <main id="main-content" class="min-h-[80vh]">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    @php
        $footerMenuItems = [];
        if (app()->bound('current_store')) {
            $footerMenu = \App\Models\NavigationMenu::withoutGlobalScopes()
                ->where('store_id', app('current_store')->id)
                ->where('handle', 'footer-menu')
                ->first();
            if ($footerMenu) {
                $footerMenuItems = $navigationService->buildTree($footerMenu);
            }
        }
        $storeName = app()->bound('current_store') ? app('current_store')->name : config('app.name');
    @endphp
    <footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900" role="contentinfo">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-3">
                {{-- Quick links --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Quick Links</h3>
                    <ul class="mt-4 space-y-3">
                        @foreach($footerMenuItems as $item)
                            <li>
                                <a href="{{ $item['url'] }}"
                                   class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Customer service --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer Service</h3>
                    <ul class="mt-4 space-y-3">
                        <li>
                            <a href="/pages/shipping-policy" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                Shipping Policy
                            </a>
                        </li>
                        <li>
                            <a href="/pages/contact-us" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                Contact Us
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Store info --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $storeName }}</h3>
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        Your one-stop shop for quality products.
                    </p>
                </div>
            </div>

            {{-- Copyright --}}
            <div class="mt-12 border-t border-gray-200 pt-8 dark:border-gray-800">
                <p class="text-center text-xs text-gray-500 dark:text-gray-500">
                    &copy; {{ date('Y') }} {{ $storeName }}. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
