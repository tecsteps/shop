@php
    $themeSettings = app(\App\Services\ThemeSettingsService::class);
    $store = app()->bound('current_store') ? app('current_store') : null;
    $storeName = $store?->name ?? config('app.name');
    $announcementBar = $themeSettings->get('announcement_bar', []);
    $stickyHeader = $themeSettings->get('sticky_header', false);
    $darkMode = $themeSettings->get('dark_mode', 'system');

    $navigationService = app(\App\Services\NavigationService::class);
    $mainMenu = null;
    $footerMenu = null;
    if ($store) {
        $mainMenu = \App\Models\NavigationMenu::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', 'main-menu')
            ->first();
        $footerMenu = \App\Models\NavigationMenu::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', 'footer-menu')
            ->first();
    }
    $mainNavItems = $mainMenu ? $navigationService->buildTree($mainMenu) : [];
    $footerNavItems = $footerMenu ? $navigationService->buildTree($footerMenu) : [];
    $socialLinks = $themeSettings->get('footer.social_links', []);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="{{ $metaDescription ?? '' }}">

        <title>{{ isset($title) ? $title . ' - ' . $storeName : $storeName }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white text-gray-700 antialiased dark:bg-gray-950 dark:text-gray-300">
        {{-- Skip Link --}}
        <a href="#main-content"
           class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-gray-900 focus:shadow-lg focus:ring-2 focus:ring-blue-500 dark:focus:bg-gray-900 dark:focus:text-white">
            Skip to main content
        </a>

        {{-- Announcement Bar --}}
        @if(! empty($announcementBar['enabled']))
            <div x-data="{ dismissed: localStorage.getItem('announcement_dismissed') === 'true' }"
                 x-show="!dismissed"
                 x-cloak
                 class="relative bg-gray-800 px-4 py-2.5 text-center text-sm text-white dark:bg-gray-100 dark:text-gray-900"
                 @if(! empty($announcementBar['bg_color']))
                     style="background-color: {{ $announcementBar['bg_color'] }}"
                 @endif>
                <p class="mx-auto max-w-7xl">
                    {{ $announcementBar['text'] ?? '' }}
                    @if(! empty($announcementBar['link']))
                        <a href="{{ $announcementBar['link'] }}" class="underline hover:no-underline">
                            Learn more
                        </a>
                    @endif
                </p>
                <button @click="dismissed = true; localStorage.setItem('announcement_dismissed', 'true')"
                        class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-white/80 hover:text-white dark:text-gray-900/80 dark:hover:text-gray-900"
                        aria-label="Dismiss announcement">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        {{-- Header --}}
        <header @class([
            'border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950',
            'sticky top-0 z-50 bg-white/95 backdrop-blur-sm dark:bg-gray-950/95' => $stickyHeader,
        ])>
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                {{-- Mobile: Hamburger --}}
                <button x-data
                        @click="$dispatch('toggle-mobile-nav')"
                        class="lg:hidden p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                        aria-label="Open navigation menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="text-xl font-bold text-gray-900 dark:text-white lg:text-2xl">
                    {{ $storeName }}
                </a>

                {{-- Desktop Navigation --}}
                <nav class="hidden lg:flex lg:items-center lg:gap-8" aria-label="Main navigation">
                    @foreach($mainNavItems as $item)
                        <a href="{{ $item['url'] }}"
                           class="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                {{-- Right Icons --}}
                <div class="flex items-center gap-3">
                    {{-- Search --}}
                    <button class="hidden p-2 text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white lg:block"
                            aria-label="Search">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </button>

                    {{-- Cart --}}
                    <button class="relative p-2 text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                            aria-label="Open cart">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </button>

                    {{-- Account --}}
                    <a href="{{ route('storefront.account.login') }}"
                       class="p-2 text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                       aria-label="Account">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </a>
                </div>
            </div>
        </header>

        {{-- Mobile Navigation Drawer --}}
        <div x-data="{ open: false }"
             @toggle-mobile-nav.window="open = !open"
             x-cloak>
            {{-- Backdrop --}}
            <div x-show="open"
                 x-transition:enter="transition-opacity duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="open = false"
                 class="fixed inset-0 z-50 bg-black/50"></div>

            {{-- Drawer --}}
            <div x-show="open"
                 x-transition:enter="transition-transform duration-300"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition-transform duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="fixed inset-y-0 left-0 z-50 w-80 max-w-full bg-white shadow-xl dark:bg-gray-950"
                 role="dialog"
                 aria-label="Mobile navigation">
                <div class="flex h-full flex-col">
                    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4 dark:border-gray-800">
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">Menu</span>
                        <button @click="open = false"
                                class="p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                                aria-label="Close navigation menu">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <nav class="flex-1 overflow-y-auto px-4 py-4" aria-label="Mobile navigation">
                        @foreach($mainNavItems as $item)
                            <a href="{{ $item['url'] }}"
                               class="block py-3 text-base font-medium text-gray-700 transition-colors hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                    <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-800">
                        <a href="{{ route('storefront.account.login') }}"
                           class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            Account
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <main id="main-content" class="min-h-[calc(100vh-4rem)]">
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-8 md:grid-cols-3 lg:grid-cols-4">
                    {{-- Footer Navigation --}}
                    @if(count($footerNavItems) > 0)
                        <div>
                            <h3 class="mb-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Quick Links
                            </h3>
                            <ul class="space-y-3">
                                @foreach($footerNavItems as $item)
                                    <li>
                                        <a href="{{ $item['url'] }}"
                                           class="text-sm text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                            {{ $item['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Store Info --}}
                    <div>
                        <h3 class="mb-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Store
                        </h3>
                        <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                            <li>{{ $storeName }}</li>
                        </ul>
                    </div>
                </div>

                {{-- Social Links --}}
                @if(count($socialLinks) > 0)
                    <div class="mt-8 flex gap-4">
                        @foreach($socialLinks as $platform => $url)
                            @if($url)
                                <a href="{{ $url }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-gray-400 transition-colors hover:text-gray-600 dark:hover:text-gray-300"
                                   aria-label="{{ ucfirst($platform) }}">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" />
                                    </svg>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- Copyright --}}
                <div class="mt-8 border-t border-gray-200 pt-8 dark:border-gray-800">
                    <p class="text-sm text-gray-400">
                        &copy; {{ date('Y') }} {{ $storeName }}. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>

        {{-- Cart Drawer Placeholder --}}
        {{-- Will be replaced with Livewire CartDrawer component in Phase 4 --}}

        @livewireScripts
    </body>
</html>
