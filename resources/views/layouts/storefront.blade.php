<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? ($currentStore->name ?? config('app.name')) }}</title>

        @if(isset($metaDescription))
            <meta name="description" content="{{ $metaDescription }}">
        @endif

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-900 dark:text-zinc-100">
        {{-- Skip to content --}}
        <a href="#main-content"
           class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-md focus:bg-zinc-900 focus:px-4 focus:py-2 focus:text-white focus:outline-none dark:focus:bg-white dark:focus:text-zinc-900">
            Skip to main content
        </a>

        @php
            $themeService = app(\App\Services\ThemeSettingsService::class);
            $store = $currentStore ?? null;
            $settings = $store ? $themeService->getSettings($store) : $themeService->defaults();
            $navService = app(\App\Services\NavigationService::class);
        @endphp

        {{-- Announcement Bar --}}
        @if(data_get($settings, 'announcement_bar.enabled', false))
            <div x-data="{ dismissed: localStorage.getItem('announcement-dismissed') === 'true' }"
                 x-show="!dismissed"
                 x-cloak
                 class="relative bg-zinc-900 px-4 py-2.5 text-center text-sm text-white dark:bg-zinc-100 dark:text-zinc-900">
                @if(data_get($settings, 'announcement_bar.link'))
                    <a href="{{ data_get($settings, 'announcement_bar.link') }}" class="underline-offset-2 hover:underline">
                        {{ data_get($settings, 'announcement_bar.text', '') }}
                    </a>
                @else
                    <span>{{ data_get($settings, 'announcement_bar.text', '') }}</span>
                @endif
                <button @click="dismissed = true; localStorage.setItem('announcement-dismissed', 'true')"
                        class="absolute right-3 top-1/2 -translate-y-1/2 p-1 hover:opacity-70"
                        aria-label="Dismiss announcement">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        {{-- Desktop Header --}}
        <header @class([
            'border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900',
            'sticky top-0 z-40 backdrop-blur-md bg-white/90 dark:bg-zinc-900/90' => data_get($settings, 'header.sticky', false),
        ])>
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                {{-- Logo --}}
                <a href="/" class="shrink-0">
                    @if(data_get($settings, 'header.logo_url'))
                        <img src="{{ data_get($settings, 'header.logo_url') }}" alt="{{ $currentStore->name ?? config('app.name') }}" class="h-8">
                    @else
                        <span class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $currentStore->name ?? config('app.name') }}</span>
                    @endif
                </a>

                {{-- Desktop Navigation --}}
                @php
                    $mainMenu = $store ? \App\Models\NavigationMenu::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'main-menu')->first() : null;
                    $navItems = $mainMenu ? $navService->buildTree($mainMenu) : [];
                @endphp
                <nav class="hidden items-center gap-6 lg:flex" aria-label="Main navigation">
                    @foreach($navItems as $item)
                        <a href="{{ $item['url'] }}"
                           class="text-sm font-medium text-zinc-700 transition hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                {{-- Action Icons --}}
                <div class="flex items-center gap-2">
                    {{-- Search --}}
                    <button class="hidden rounded-md p-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 lg:block dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                            aria-label="Search">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    </button>

                    {{-- Account --}}
                    <a href="{{ auth('customer')->check() ? route('storefront.account') : route('storefront.login') }}"
                       class="hidden rounded-md p-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 lg:block dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                       aria-label="Account">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    </a>

                    {{-- Cart --}}
                    <a href="{{ route('storefront.cart') }}"
                       class="relative rounded-md p-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                       aria-label="Cart">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                    </a>

                    {{-- Mobile hamburger --}}
                    <button x-data @click="$dispatch('toggle-mobile-nav')"
                            class="rounded-md p-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 lg:hidden dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                            aria-label="Open menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>
                </div>
            </div>
        </header>

        {{-- Mobile Navigation Drawer --}}
        <div x-data="{ open: false }"
             @toggle-mobile-nav.window="open = !open"
             @keydown.escape.window="open = false"
             x-cloak>
            {{-- Overlay --}}
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
            <nav x-show="open"
                 x-transition:enter="transition-transform duration-300"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition-transform duration-300"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 x-trap.noscroll="open"
                 class="fixed inset-y-0 left-0 z-50 w-80 overflow-y-auto bg-white p-6 shadow-xl dark:bg-zinc-800"
                 aria-label="Mobile navigation">
                <div class="mb-6 flex items-center justify-between">
                    <span class="text-lg font-semibold dark:text-white">{{ $currentStore->name ?? config('app.name') }}</span>
                    <button @click="open = false" class="rounded-md p-1 text-zinc-500 hover:text-zinc-900 dark:hover:text-white" aria-label="Close menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex flex-col gap-1">
                    @foreach($navItems as $item)
                        <a href="{{ $item['url'] }}"
                           class="rounded-md px-3 py-2 text-base font-medium text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
                <div class="mt-6 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <a href="{{ auth('customer')->check() ? route('storefront.account') : route('storefront.login') }}"
                       class="flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium text-zinc-700 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        {{ auth('customer')->check() ? __('My Account') : __('Log In') }}
                    </a>
                </div>
            </nav>
        </div>

        {{-- Main Content --}}
        <main id="main-content" class="min-h-[calc(100vh-4rem)]">
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3 lg:grid-cols-4">
                    {{-- Store Info --}}
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">
                            {{ $currentStore->name ?? config('app.name') }}
                        </h3>
                        @if($store && $store->settings)
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ data_get($store->settings->settings_json ?? [], 'contact_email', '') }}
                            </p>
                        @endif
                        {{-- Social Links --}}
                        @php $socialLinks = data_get($settings, 'footer.social_links', []); @endphp
                        @if(!empty($socialLinks))
                            <div class="mt-4 flex gap-3">
                                @foreach($socialLinks as $social)
                                    <a href="{{ $social['url'] }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white"
                                       aria-label="{{ ucfirst($social['platform']) }}">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Footer Navigation --}}
                    @php
                        $footerMenu = $store ? \App\Models\NavigationMenu::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', 'footer-menu')->first() : null;
                        $footerItems = $footerMenu ? $navService->buildTree($footerMenu) : [];
                    @endphp
                    @if(!empty($footerItems))
                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">Links</h3>
                            <ul class="mt-4 space-y-2">
                                @foreach($footerItems as $item)
                                    <li>
                                        <a href="{{ $item['url'] }}" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                            {{ $item['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                {{-- Copyright --}}
                <div class="mt-8 border-t border-zinc-200 pt-8 dark:border-zinc-700">
                    <p class="text-center text-sm text-zinc-500 dark:text-zinc-400">
                        &copy; {{ date('Y') }} {{ $currentStore->name ?? config('app.name') }}. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>

        {{-- Cart Drawer --}}
        <livewire:storefront.cart-drawer />

        @fluxScripts
        @livewireScripts
    </body>
</html>
