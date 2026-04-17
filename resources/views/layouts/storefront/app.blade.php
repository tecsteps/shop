<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $metaDescription ?? '' }}">
    <title>{{ ($title ?? '') ? $title . ' - ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white text-gray-700 dark:bg-gray-950 dark:text-gray-300 antialiased">
    {{-- Skip to content --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:shadow-lg focus:ring-2 focus:ring-blue-500 dark:focus:bg-gray-800 dark:focus:text-white">
        Skip to main content
    </a>

    {{-- Announcement bar --}}
    @php
        $themeSettings = app(\App\Services\ThemeSettingsService::class);
    @endphp
    @if($themeSettings->get('show_announcement_bar'))
        <div x-data="{ dismissed: localStorage.getItem('announcement_dismissed') === 'true' }"
             x-show="!dismissed"
             x-cloak
             class="relative bg-gray-900 text-white text-center text-sm py-2 px-4 dark:bg-gray-800">
            <p class="pr-8">
                @if($themeSettings->get('announcement_link'))
                    <a href="{{ $themeSettings->get('announcement_link') }}" class="underline">
                        {{ $themeSettings->get('announcement_text', '') }}
                    </a>
                @else
                    {{ $themeSettings->get('announcement_text', '') }}
                @endif
            </p>
            <button @click="dismissed = true; localStorage.setItem('announcement_dismissed', 'true')"
                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-white/70 hover:text-white"
                    aria-label="Dismiss announcement">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Header --}}
    <header class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950 {{ $themeSettings->get('sticky_header') ? 'sticky top-0 z-40 backdrop-blur-sm bg-white/90 dark:bg-gray-950/90' : '' }}">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                {{-- Mobile hamburger --}}
                <button x-data x-on:click="$dispatch('toggle-mobile-nav')"
                        class="lg:hidden p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        aria-label="Open navigation menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18M3 6h18M3 18h18" />
                    </svg>
                </button>

                {{-- Logo --}}
                <a href="{{ route('storefront.home') }}" class="flex-shrink-0">
                    @if($themeSettings->get('logo_url'))
                        <img src="{{ $themeSettings->get('logo_url') }}"
                             alt="{{ config('app.name') }}"
                             class="h-10 max-h-8 w-auto lg:max-h-10">
                    @else
                        <span class="text-xl font-bold text-gray-900 dark:text-white">{{ app()->bound('current_store') ? app('current_store')->name : config('app.name') }}</span>
                    @endif
                </a>

                {{-- Desktop navigation --}}
                <nav class="hidden lg:flex lg:items-center lg:gap-6" aria-label="Main navigation">
                    @php
                        $navService = app(\App\Services\NavigationService::class);
                        $mainMenu = \App\Models\NavigationMenu::where('handle', 'main-menu')->first();
                        $navItems = $mainMenu ? $navService->buildTree($mainMenu) : [];
                    @endphp
                    @foreach($navItems as $item)
                        <a href="{{ $item['url'] }}"
                           class="text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                {{-- Right icons --}}
                <div class="flex items-center gap-3">
                    {{-- Search --}}
                    <button x-data x-on:click="$dispatch('open-search-modal')"
                            class="hidden sm:block p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            aria-label="Search">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </button>

                    {{-- Cart --}}
                    <button x-data x-on:click="$dispatch('open-cart-drawer')"
                            class="relative p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            aria-label="Open cart">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </button>

                    {{-- Account --}}
                    <a href="{{ route('customer.login') }}"
                       class="hidden sm:block p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                       aria-label="Account">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        {{-- Mobile navigation drawer --}}
        <div x-data="{ mobileNavOpen: false }"
             x-on:toggle-mobile-nav.window="mobileNavOpen = !mobileNavOpen"
             class="lg:hidden">
            <div x-show="mobileNavOpen"
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="mobileNavOpen = false"
                 class="fixed inset-0 z-40 bg-black/50"></div>

            <nav x-show="mobileNavOpen"
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 x-trap.noscroll="mobileNavOpen"
                 class="fixed inset-y-0 left-0 z-50 w-72 bg-white dark:bg-gray-900 shadow-xl"
                 aria-label="Mobile navigation">
                <div class="flex items-center justify-end p-4">
                    <button @click="mobileNavOpen = false"
                            class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            aria-label="Close navigation menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="px-4 pb-6 space-y-1">
                    @foreach($navItems as $item)
                        <a href="{{ $item['url'] }}"
                           class="block rounded-md px-3 py-3 text-base font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <a href="{{ route('customer.login') }}"
                           class="block rounded-md px-3 py-3 text-base font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                            Account
                        </a>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    {{-- Main content --}}
    <main id="main-content" class="min-h-screen">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900" role="contentinfo">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-3 lg:grid-cols-4">
                {{-- Footer navigation --}}
                @php
                    $footerMenu = \App\Models\NavigationMenu::where('handle', 'footer-menu')->first();
                    $footerItems = $footerMenu ? $navService->buildTree($footerMenu) : [];
                @endphp
                @if(count($footerItems) > 0)
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Links</h3>
                        <ul class="mt-4 space-y-3">
                            @foreach($footerItems as $item)
                                <li>
                                    <a href="{{ $item['url'] }}"
                                       class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors">
                                        {{ $item['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Store info --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Store</h3>
                    <div class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <p>{{ app()->bound('current_store') ? app('current_store')->name : config('app.name') }}</p>
                    </div>
                </div>
            </div>

            {{-- Social links --}}
            @if($themeSettings->get('social_links'))
                <div class="mt-8 flex gap-4">
                    @foreach($themeSettings->get('social_links', []) as $platform => $url)
                        <a href="{{ $url }}"
                           class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                           aria-label="{{ ucfirst($platform) }}"
                           target="_blank"
                           rel="noopener noreferrer">
                            <span class="h-5 w-5">{{ ucfirst($platform) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Copyright --}}
            <div class="mt-8 border-t border-gray-200 pt-8 dark:border-gray-700">
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    &copy; {{ date('Y') }} {{ app()->bound('current_store') ? app('current_store')->name : config('app.name') }}. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    {{-- Cart drawer --}}
    @livewire('storefront.cart-drawer')

    {{-- Search modal --}}
    @livewire('storefront.search.modal')

    @fluxScripts
    @livewireScripts
</body>
</html>
