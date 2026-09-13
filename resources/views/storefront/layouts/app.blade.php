<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="{{ $metaDescription ?? '' }}" />
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="alternate icon" href="/favicon.ico" />
    @stack('meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
</head>
@php
    $store = app(\App\Tenancy\CurrentStore::class)->get() ?? ($currentStore ?? null);
    $themeSettings = $store ? app(\App\Services\ThemeService::class)->settings($store) : [];
    $navigation = $store ? app(\App\Services\NavigationService::class) : null;
    $mainMenu = ($store && $navigation) ? $navigation->menu($store, 'main-menu') : null;
    $mainNav = ($mainMenu && $navigation) ? $navigation->buildTree($mainMenu) : [];
    $footerMenu = ($store && $navigation) ? $navigation->menu($store, 'footer-menu') : null;
    $footerNav = ($footerMenu && $navigation) ? $navigation->buildTree($footerMenu) : [];
    $customer = auth('customer')->user();
@endphp
<body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100" style="scroll-behavior: smooth;">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-zinc-900 focus:px-4 focus:py-2 focus:text-white focus:shadow-lg focus:ring-2 dark:focus:bg-white dark:focus:text-zinc-900">Skip to main content</a>

    @if (($themeSettings['announcement']['show'] ?? false) && ($themeSettings['announcement']['text'] ?? '') !== '')
        <div class="w-full bg-zinc-900 px-4 py-2 text-center text-sm text-white dark:bg-white dark:text-zinc-900">
            @if (($themeSettings['announcement']['link'] ?? null))
                <a href="{{ $themeSettings['announcement']['link'] }}" class="underline">{{ $themeSettings['announcement']['text'] }}</a>
            @else
                {{ $themeSettings['announcement']['text'] }}
            @endif
        </div>
    @endif

    <header role="banner" class="{{ ($themeSettings['header']['sticky'] ?? false) ? 'sticky top-0 z-40 border-b border-zinc-200 bg-white/90 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90' : 'border-b border-zinc-200 dark:border-zinc-800' }}">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4">
            <div class="flex items-center gap-2">
                <details class="lg:hidden">
                    <summary class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Open menu">
                        <flux:icon.bars-2 class="size-6" variant="outline" />
                    </summary>
                    <nav aria-label="Mobile navigation" class="absolute left-0 top-16 z-40 w-72 border-r border-zinc-200 bg-white p-4 shadow-lg dark:border-zinc-800 dark:bg-zinc-950">
                        <ul class="flex flex-col gap-1">
                            @foreach ($mainNav as $item)
                                <li wire:key="mobile-nav-{{ $loop->index }}">
                                    <a href="{{ $item['url'] }}" class="block rounded px-3 py-2.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ $item['label'] }}</a>
                                </li>
                            @endforeach
                            <li>
                                <a href="{{ $customer ? route('customer.account') : route('customer.login') }}" class="block rounded px-3 py-2.5 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">Account</a>
                            </li>
                        </ul>
                    </nav>
                </details>
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    @if (($themeSettings['header']['logo_url'] ?? null))
                        <img src="{{ $themeSettings['header']['logo_url'] }}" alt="{{ $store?->name ?? config('app.name') }}" class="h-8 max-h-10 lg:h-10" />
                    @else
                        <span class="text-lg font-bold">{{ $store?->name ?? config('app.name') }}</span>
                    @endif
                </a>
            </div>

            <nav aria-label="Main navigation" class="hidden lg:block">
                <ul class="flex items-center gap-1">
                    @foreach ($mainNav as $item)
                        <li wire:key="nav-{{ $loop->index }}">
                            <a href="{{ $item['url'] }}" class="rounded px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="flex items-center gap-1">
                <button type="button" x-data="" x-on:click="$dispatch('open-search-modal')" class="flex h-11 w-11 items-center justify-center rounded hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Search">
                    <flux:icon.magnifying-glass class="size-5" variant="outline" />
                </button>
                <button type="button" x-data="" x-on:click="$dispatch('open-cart-drawer')" class="relative flex h-11 w-11 items-center justify-center rounded hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Open cart">
                    <flux:icon.shopping-bag class="size-5" variant="outline" />
                    <livewire:storefront.cart-count />
                </button>
                <a href="{{ $customer ? route('customer.account') : route('customer.login') }}" class="hidden h-11 w-11 items-center justify-center rounded hover:bg-zinc-100 sm:flex dark:hover:bg-zinc-800" aria-label="Account">
                    <flux:icon.user class="size-5" variant="outline" />
                </a>
            </div>
        </div>
    </header>

    <main id="main-content" class="min-h-screen">
        {{ $slot }}
    </main>

    <footer role="contentinfo" class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto max-w-7xl px-4 py-12">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-3">
                <nav aria-label="Footer">
                    <h2 class="text-xs font-semibold uppercase tracking-wider">Links</h2>
                    <ul class="mt-4 flex flex-col gap-2">
                        @foreach ($footerNav as $item)
                            <li wire:key="footer-nav-{{ $loop->index }}">
                                <a href="{{ $item['url'] }}" class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ $item['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-wider">Store</h2>
                    <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $store?->name ?? config('app.name') }}</p>
                    @if (($themeSettings['footer']['contact_email'] ?? null))
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $themeSettings['footer']['contact_email'] }}</p>
                    @endif
                </div>
            </div>
            <p class="mt-8 text-sm text-zinc-500 dark:text-zinc-400">&copy; {{ now()->year }} {{ $store?->name ?? config('app.name') }}. All rights reserved.</p>
        </div>
    </footer>

    <livewire:storefront.cart-drawer />
    <livewire:storefront.search-modal />

    @livewireScripts
    @fluxScripts
</body>
</html>
