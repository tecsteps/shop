<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $metaDescription ?? '' }}">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white text-zinc-700 antialiased dark:bg-zinc-950 dark:text-zinc-300">
    @php
        $themeSettings = app(\App\Services\ThemeSettingsService::class)->getSettings();
        $store = app()->bound('current_store') ? app('current_store') : null;
        $storeName = $store?->name ?? config('app.name');
    @endphp

    {{-- Skip link --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-zinc-900 focus:shadow-lg focus:ring-2 focus:ring-blue-500 dark:focus:bg-zinc-800 dark:focus:text-white">
        Skip to main content
    </a>

    {{-- Announcement bar --}}
    @if($themeSettings['announcement_bar_enabled'] ?? false)
        <div x-data="{ dismissed: localStorage.getItem('announcement_dismissed') === 'true' }"
             x-show="!dismissed"
             x-cloak
             class="relative w-full py-2 px-4 text-center text-sm text-white"
             style="background-color: {{ $themeSettings['announcement_bar_bg_color'] ?? '#1f2937' }}">
            @if($themeSettings['announcement_bar_link'] ?? false)
                <a href="{{ $themeSettings['announcement_bar_link'] }}" class="underline hover:opacity-80">
                    {{ $themeSettings['announcement_bar_text'] ?? '' }}
                </a>
            @else
                <span>{{ $themeSettings['announcement_bar_text'] ?? '' }}</span>
            @endif
            <button @click="dismissed = true; localStorage.setItem('announcement_dismissed', 'true')"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-white/80 hover:text-white"
                    aria-label="Dismiss announcement">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Header --}}
    <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950 {{ ($themeSettings['sticky_header'] ?? false) ? 'sticky top-0 z-40 backdrop-blur-sm bg-white/90 dark:bg-zinc-950/90' : '' }}"
            x-data="{ mobileMenuOpen: false }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            {{-- Desktop header --}}
            <div class="flex h-16 items-center justify-between">
                {{-- Mobile hamburger --}}
                <button @click="mobileMenuOpen = true"
                        class="lg:hidden rounded-md p-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                        aria-label="Open menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center text-xl font-bold text-zinc-900 dark:text-white lg:flex-none">
                    {{ $storeName }}
                </a>

                {{-- Desktop navigation --}}
                <nav class="hidden lg:flex lg:items-center lg:gap-6" aria-label="Main navigation">
                    @php
                        $navigationService = app(\App\Services\NavigationService::class);
                        $mainMenu = \App\Models\NavigationMenu::withoutGlobalScopes()
                            ->where('store_id', $store?->id)
                            ->where('handle', 'main-menu')
                            ->first();
                        $navItems = $mainMenu ? $navigationService->buildTree($mainMenu) : [];
                    @endphp
                    @foreach($navItems as $item)
                        <a href="{{ $item['url'] }}"
                           class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                {{-- Right side icons --}}
                <div class="flex items-center gap-3">
                    {{-- Search --}}
                    <button class="rounded-md p-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                            aria-label="Search">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </button>

                    {{-- Cart --}}
                    <a href="{{ route('storefront.cart') }}"
                       class="relative rounded-md p-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                       aria-label="Cart">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </a>

                    {{-- Account --}}
                    @auth('customer')
                        <a href="{{ route('storefront.account') }}"
                           class="hidden sm:block rounded-md p-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                           aria-label="Account">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </a>
                    @else
                        <a href="{{ route('storefront.login') }}"
                           class="hidden sm:block rounded-md p-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                           aria-label="Login">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </a>
                    @endauth
                </div>
            </div>
        </div>

        {{-- Mobile navigation drawer --}}
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
                 class="fixed inset-y-0 left-0 w-full max-w-xs bg-white p-6 dark:bg-zinc-900">
                <div class="flex items-center justify-between mb-6">
                    <span class="text-lg font-bold text-zinc-900 dark:text-white">{{ $storeName }}</span>
                    <button @click="mobileMenuOpen = false"
                            class="rounded-md p-2 text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                            aria-label="Close menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <nav class="flex flex-col gap-1">
                    @foreach($navItems as $item)
                        <a href="{{ $item['url'] }}"
                           class="rounded-md px-3 py-2 text-base font-medium text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
                <div class="mt-auto pt-6 border-t border-zinc-200 dark:border-zinc-700">
                    @auth('customer')
                        <a href="{{ route('storefront.account') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            My Account
                        </a>
                    @else
                        <a href="{{ route('storefront.login') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            Login
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    {{-- Main content --}}
    <main id="main-content" class="min-h-[calc(100vh-4rem)]">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-3">
                {{-- Quick links --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Shop</h3>
                    <ul class="mt-4 space-y-2">
                        <li><a href="/collections" class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Collections</a></li>
                    </ul>
                </div>

                {{-- Info --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Info</h3>
                    <ul class="mt-4 space-y-2">
                        <li><a href="/pages/about" class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">About</a></li>
                    </ul>
                </div>

                {{-- Store info --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $storeName }}</h3>
                    <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">Quality fashion for everyone.</p>
                </div>
            </div>

            {{-- Social links --}}
            @if(($themeSettings['social_facebook'] ?? '') || ($themeSettings['social_instagram'] ?? '') || ($themeSettings['social_twitter'] ?? ''))
                <div class="mt-8 flex gap-4">
                    @if($themeSettings['social_facebook'] ?? '')
                        <a href="{{ $themeSettings['social_facebook'] }}" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" aria-label="Facebook">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                        </a>
                    @endif
                    @if($themeSettings['social_instagram'] ?? '')
                        <a href="{{ $themeSettings['social_instagram'] }}" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" aria-label="Instagram">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"/></svg>
                        </a>
                    @endif
                </div>
            @endif

            {{-- Copyright --}}
            <div class="mt-8 border-t border-zinc-200 pt-8 dark:border-zinc-700">
                <p class="text-xs text-zinc-400">&copy; {{ date('Y') }} {{ $storeName }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    {{-- Cart drawer placeholder (Phase 4) --}}
    <livewire:storefront.cart-drawer />

    @livewireScripts
</body>
</html>
