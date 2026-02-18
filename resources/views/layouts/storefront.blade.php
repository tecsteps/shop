<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $metaDescription ?? '' }}">
    <title>{{ ($title ?? 'Shop') . ' - ' . config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    {{-- Skip link --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:left-2 focus:top-2 focus:z-50 focus:rounded focus:bg-white focus:px-4 focus:py-2 focus:shadow-lg focus:ring-2 focus:ring-blue-500 dark:focus:bg-gray-800">
        Skip to main content
    </a>

    @php
        $themeSettings = app(\App\Services\ThemeSettingsService::class);
        $announcement = $themeSettings->setting('announcement_bar', []);
        $stickyHeader = $themeSettings->setting('sticky_header', false);
    @endphp

    {{-- Announcement Bar --}}
    @if (!empty($announcement['enabled']))
        <div x-data="{ dismissed: localStorage.getItem('announcement_dismissed') === 'true' }"
             x-show="!dismissed"
             x-cloak
             class="relative bg-gray-900 px-4 py-2 text-center text-sm text-white dark:bg-gray-100 dark:text-gray-900">
            <span>{{ $announcement['text'] ?? '' }}</span>
            @if (!empty($announcement['link']))
                <a href="{{ $announcement['link'] }}" class="ml-1 underline">Learn more</a>
            @endif
            <button @click="dismissed = true; localStorage.setItem('announcement_dismissed', 'true')"
                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1" aria-label="Dismiss announcement">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    {{-- Header --}}
    <header @class([
        'border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950',
        'sticky top-0 z-40 backdrop-blur-sm bg-white/90 dark:bg-gray-950/90' => $stickyHeader,
    ])>
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
             x-data="{ mobileMenuOpen: false }">
            {{-- Mobile hamburger --}}
            <button @click="mobileMenuOpen = true"
                    class="lg:hidden rounded p-2 hover:bg-gray-100 dark:hover:bg-gray-800"
                    aria-label="Open mobile navigation">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Logo --}}
            <a href="/" class="flex items-center">
                <span class="text-xl font-bold">
                    @if (app()->bound('current_store'))
                        {{ app('current_store')->name }}
                    @else
                        {{ config('app.name') }}
                    @endif
                </span>
            </a>

            {{-- Desktop Navigation --}}
            @php
                $navService = app(\App\Services\NavigationService::class);
                $mainMenu = null;
                if (app()->bound('current_store')) {
                    $mainMenu = \App\Models\NavigationMenu::query()
                        ->withoutGlobalScopes()
                        ->where('store_id', app('current_store')->id)
                        ->where('handle', 'main-menu')
                        ->first();
                }
                $navItems = $mainMenu ? $navService->buildTree($mainMenu) : collect();
            @endphp

            <nav class="hidden lg:flex lg:items-center lg:space-x-8" aria-label="Main navigation">
                @foreach ($navItems as $item)
                    <a href="{{ $item['url'] }}"
                       class="text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- Right icons --}}
            <div class="flex items-center space-x-4">
                {{-- Search --}}
                <button class="hidden rounded p-2 hover:bg-gray-100 dark:hover:bg-gray-800 lg:block"
                        aria-label="Search">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>

                {{-- Cart --}}
                <a href="/cart" class="relative rounded p-2 hover:bg-gray-100 dark:hover:bg-gray-800"
                   aria-label="Cart">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </a>

                {{-- Account --}}
                @auth('customer')
                    <a href="/account" class="rounded p-2 hover:bg-gray-100 dark:hover:bg-gray-800"
                       aria-label="Account">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('customer.login') }}" class="rounded p-2 hover:bg-gray-100 dark:hover:bg-gray-800"
                       aria-label="Log in">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </a>
                @endauth
            </div>

            {{-- Mobile Navigation Drawer --}}
            <div x-show="mobileMenuOpen"
                 x-cloak
                 class="fixed inset-0 z-50 lg:hidden"
                 role="dialog"
                 aria-modal="true"
                 aria-label="Mobile navigation">
                {{-- Backdrop --}}
                <div x-show="mobileMenuOpen"
                     x-transition:enter="transition-opacity ease-linear duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-linear duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="mobileMenuOpen = false"
                     class="fixed inset-0 bg-black/50"></div>

                {{-- Drawer --}}
                <div x-show="mobileMenuOpen"
                     x-transition:enter="transition ease-in-out duration-300 transform"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in-out duration-300 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full"
                     x-trap.noscroll="mobileMenuOpen"
                     class="fixed inset-y-0 left-0 w-full max-w-xs bg-white p-6 shadow-xl dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-bold">Menu</span>
                        <button @click="mobileMenuOpen = false" class="rounded p-2" aria-label="Close menu">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <nav class="mt-6 space-y-1" aria-label="Mobile navigation">
                        @foreach ($navItems as $item)
                            <a href="{{ $item['url'] }}"
                               class="block rounded-lg px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>

                    <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
                        @auth('customer')
                            <a href="/account" class="block rounded-lg px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                                My Account
                            </a>
                        @else
                            <a href="{{ route('customer.login') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                                Log in
                            </a>
                        @endauth
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
    <footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900" role="contentinfo">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                {{-- Column 1: Quick links --}}
                @php
                    $footerMenu = null;
                    if (app()->bound('current_store')) {
                        $footerMenu = \App\Models\NavigationMenu::query()
                            ->withoutGlobalScopes()
                            ->where('store_id', app('current_store')->id)
                            ->where('handle', 'footer-menu')
                            ->first();
                    }
                    $footerItems = $footerMenu ? $navService->buildTree($footerMenu) : collect();
                @endphp

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Quick Links</h3>
                    <ul class="mt-4 space-y-2">
                        @foreach ($footerItems as $item)
                            <li>
                                <a href="{{ $item['url'] }}"
                                   class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Column 2: Help --}}
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Help</h3>
                    <ul class="mt-4 space-y-2">
                        <li>
                            <a href="/pages/shipping-policy" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                Shipping Policy
                            </a>
                        </li>
                        <li>
                            <a href="/pages/contact" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                Contact Us
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Column 3: Store info --}}
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Store</h3>
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        @if (app()->bound('current_store'))
                            {{ app('current_store')->name }}
                        @else
                            {{ config('app.name') }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="mt-8 border-t border-gray-200 pt-8 dark:border-gray-700">
                <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                    &copy; {{ date('Y') }}
                    @if (app()->bound('current_store'))
                        {{ app('current_store')->name }}
                    @else
                        {{ config('app.name') }}
                    @endif
                    . All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
