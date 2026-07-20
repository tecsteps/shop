@php
    /** @var \App\Models\Store $currentStore */
    $themeSettings = app(\App\Services\ThemeSettingsService::class);
    $navigation = app(\App\Services\NavigationService::class);
    $mainMenu = $navigation->forHandle('main-menu');
    $footerMenu = $navigation->forHandle('footer-menu');
    $announcement = $themeSettings->get('announcement', []);
    $logoUrl = $themeSettings->get('header.logo_url');
    $isSticky = (bool) $themeSettings->get('header.sticky', false);
    $darkMode = $themeSettings->get('dark_mode', 'system');
    $socialLinks = $themeSettings->get('footer.social', []);
    $pageTitle = $title ?? $currentStore->name;
    $organizationJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $currentStore->name,
        'url' => url('/'),
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    @if (! empty($metaDescription))
        <meta name="description" content="{{ $metaDescription }}">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    @if (! empty($og) && is_array($og))
        <meta property="og:title" content="{{ $og['title'] ?? $pageTitle }}">
        @if (! empty($og['description']))
            <meta property="og:description" content="{{ $og['description'] }}">
        @endif
        @if (! empty($og['image']))
            <meta property="og:image" content="{{ $og['image'] }}">
        @endif
        <meta property="og:type" content="{{ $og['type'] ?? 'website' }}">
        <meta property="og:url" content="{{ url()->current() }}">
        @if (! empty($og['price_amount']))
            <meta property="product:price:amount" content="{{ $og['price_amount'] }}">
            <meta property="product:price:currency" content="{{ $og['price_currency'] ?? $currentStore->default_currency }}">
        @endif
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            const mode = @js($darkMode);
            const stored = localStorage.getItem('theme');
            const dark = mode === 'dark'
                || (mode === 'toggle' && stored === 'dark')
                || ((mode === 'system' || (mode === 'toggle' && stored === null))
                    && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    <script>
        {{-- Analytics tracking snippet (spec 05 §14.1): batches page_view and
             product_view (on product pages) to the ingestion API. Vanilla JS,
             no external libs, fully failure-safe. --}}
        (function () {
            var queue = [];

            // Public API for page components: queue an event for the next flush.
            window.shopAnalytics = {
                track: function (type, properties) {
                    queue.push({ type: type, properties: properties || {} });
                }
            };

            function uuid() {
                return (window.crypto && crypto.randomUUID)
                    ? crypto.randomUUID()
                    : String(Date.now()) + '-' + Math.random().toString(36).slice(2);
            }

            function sessionId() {
                try {
                    var id = sessionStorage.getItem('shop_analytics_session');
                    if (! id) {
                        id = uuid();
                        sessionStorage.setItem('shop_analytics_session', id);
                    }
                    return id;
                } catch (e) {
                    return 'anonymous';
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                try {
                    window.shopAnalytics.track('page_view', {
                        url: window.location.pathname,
                        referrer: document.referrer || undefined
                    });

                    var productMarker = document.querySelector('[data-analytics-product-id]');
                    if (productMarker) {
                        window.shopAnalytics.track('product_view', {
                            product_id: parseInt(productMarker.getAttribute('data-analytics-product-id'), 10),
                            url: window.location.pathname
                        });
                    }

                    var sid = sessionId();
                    var events = queue.map(function (event) {
                        return {
                            type: event.type,
                            session_id: sid,
                            client_event_id: uuid(),
                            occurred_at: new Date().toISOString(),
                            properties: event.properties
                        };
                    });

                    var body = JSON.stringify({ events: events });
                    var endpoint = '/api/storefront/v1/analytics/events';

                    if (navigator.sendBeacon) {
                        navigator.sendBeacon(endpoint, new Blob([body], { type: 'application/json' }));
                    } else {
                        fetch(endpoint, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: body,
                            keepalive: true
                        }).catch(function () {});
                    }
                } catch (e) { /* analytics must never break the page */ }
            });
        })();
    </script>
</head>
<body class="min-h-screen bg-white text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-md focus:bg-blue-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lg focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-950">
        Skip to main content
    </a>

    {{-- Announcement bar --}}
    @if (! empty($announcement['enabled']) && ! empty($announcement['text']))
        <div x-data="{ dismissed: localStorage.getItem('announcement-dismissed') === '1' }"
             x-show="!dismissed"
             class="relative bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900">
            <div class="mx-auto flex max-w-7xl items-center justify-center gap-4 px-4 py-2.5">
                <p class="text-center text-sm">
                    {{ $announcement['text'] }}
                    @if (! empty($announcement['link']))
                        <a href="{{ $announcement['link'] }}" class="underline underline-offset-2 hover:opacity-80">Learn more</a>
                    @endif
                </p>
                <button type="button"
                        @click="dismissed = true; localStorage.setItem('announcement-dismissed', '1')"
                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded p-1 hover:bg-white/10 focus:outline-hidden focus:ring-2 focus:ring-white/60 dark:hover:bg-gray-900/10"
                        aria-label="Dismiss announcement">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <header x-data="{ mobileOpen: false }"
            class="{{ $isSticky ? 'sticky top-0 z-40 bg-white/80 backdrop-blur dark:bg-gray-950/80' : 'bg-white dark:bg-gray-950' }} border-b border-gray-200 dark:border-gray-800">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            {{-- Mobile hamburger --}}
            <button type="button"
                    @click="mobileOpen = true"
                    class="-ml-2 rounded-md p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 lg:hidden dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                    aria-label="Open navigation menu"
                    aria-controls="mobile-navigation"
                    :aria-expanded="mobileOpen.toString()">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            {{-- Logo --}}
            <a href="{{ route('storefront.home') }}"
               class="flex items-center gap-2 text-lg font-bold tracking-tight text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-white">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $currentStore->name }}" class="h-8 w-auto lg:h-10">
                @else
                    <span class="truncate">{{ $currentStore->name }}</span>
                @endif
            </a>

            {{-- Desktop navigation --}}
            <nav class="hidden lg:flex lg:items-center lg:gap-6" aria-label="Main navigation">
                @foreach ($mainMenu as $item)
                    <a href="{{ $item['url'] }}"
                       class="text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-gray-300 dark:hover:text-white">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- Right icon group --}}
            <div class="flex items-center gap-1 sm:gap-2">
                <button type="button"
                        x-data
                        @click="$dispatch('open-search-modal')"
                        class="hidden rounded-md p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 sm:inline-flex dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                        aria-label="Search">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </button>
                <a href="/account"
                   class="hidden rounded-md p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 sm:inline-flex dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                   aria-label="Account">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </a>
                <button type="button"
                        x-data="{ count: 0 }"
                        @cart-updated.window="count = $event.detail.count"
                        @click="$dispatch('cart-drawer-open')"
                        class="relative rounded-md p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                        aria-label="Open cart">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" />
                    </svg>
                    <span x-show="count > 0" x-cloak x-text="count"
                          class="absolute -top-0.5 -right-0.5 flex size-5 items-center justify-center rounded-full bg-blue-600 text-[11px] font-semibold text-white"
                          aria-hidden="true"></span>
                </button>
            </div>
        </div>

        {{-- Mobile navigation drawer --}}
        <div x-show="mobileOpen" x-cloak class="lg:hidden" role="dialog" aria-modal="true" aria-label="Mobile navigation" id="mobile-navigation">
            <div x-show="mobileOpen"
                 x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="mobileOpen = false"
                 class="fixed inset-0 z-40 bg-gray-900/50 dark:bg-black/60" aria-hidden="true"></div>
            <div x-show="mobileOpen"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                 @keydown.escape.window="mobileOpen = false"
                 class="fixed inset-y-0 left-0 z-50 flex w-80 max-w-full flex-col bg-white shadow-xl dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4 dark:border-gray-800">
                    <span class="text-base font-semibold text-gray-900 dark:text-white">Menu</span>
                    <button type="button"
                            @click="mobileOpen = false"
                            class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                            aria-label="Close navigation menu">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <nav class="flex-1 overflow-y-auto px-4 py-4" aria-label="Mobile navigation">
                    <ul class="flex flex-col gap-1">
                        @foreach ($mainMenu as $item)
                            <li>
                                <a href="{{ $item['url'] }}"
                                   class="block rounded-md px-3 py-3 text-base font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:text-gray-200 dark:hover:bg-gray-800 dark:hover:text-white">
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
                <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-800">
                    <a href="/account"
                       class="block rounded-md px-3 py-3 text-base font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:text-gray-200 dark:hover:bg-gray-800 dark:hover:text-white">
                        Account
                    </a>
                </div>
            </div>
        </div>
    </header>

    {{-- Main content --}}
    <main id="main-content" class="min-h-[60vh]">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-3 lg:grid-cols-4">
                @if (count($footerMenu) > 0)
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">Shop</h2>
                        <ul class="mt-4 space-y-2">
                            @foreach ($footerMenu as $item)
                                <li>
                                    <a href="{{ $item['url'] }}" class="text-sm text-gray-600 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-gray-400 dark:hover:text-white">
                                        {{ $item['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">{{ $currentStore->name }}</h2>
                    @if (! empty($themeSettings->get('footer.about')))
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">{{ $themeSettings->get('footer.about') }}</p>
                    @endif
                </div>
            </div>

            @if (count($socialLinks) > 0)
                <div class="mt-10 flex items-center gap-4">
                    @foreach ($socialLinks as $network => $url)
                        <a href="{{ $url }}" class="text-gray-500 hover:text-gray-900 focus:outline-hidden focus:ring-2 focus:ring-blue-500 rounded dark:text-gray-400 dark:hover:text-white">
                            <span class="sr-only">{{ ucfirst($network) }}</span>
                            <span class="text-sm font-medium" aria-hidden="true">{{ ucfirst($network) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="mt-10 flex flex-col gap-4 border-t border-gray-200 pt-8 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">&copy; {{ date('Y') }} {{ $currentStore->name }}. All rights reserved.</p>
                {{-- Accepted payment methods (placeholder icons until payments land) --}}
                <div class="flex items-center gap-2 opacity-70" aria-label="Accepted payment methods">
                    <span class="rounded border border-gray-300 px-2 py-1 text-[10px] font-bold tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">VISA</span>
                    <span class="rounded border border-gray-300 px-2 py-1 text-[10px] font-bold tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">MASTERCARD</span>
                    <span class="rounded border border-gray-300 px-2 py-1 text-[10px] font-bold tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">AMEX</span>
                    <span class="rounded border border-gray-300 px-2 py-1 text-[10px] font-bold tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">PAYPAL</span>
                </div>
            </div>
        </div>
        <script type="application/ld+json">@json($organizationJsonLd)</script>
    </footer>

    {{-- Cart drawer (shell here, content in the CartDrawer Livewire component) --}}
    <div x-data="{ open: false }"
         @cart-drawer-open.window="open = true"
         @keydown.escape.window="open = false">
        <div x-show="open" x-cloak role="dialog" aria-modal="true" aria-label="Shopping cart">
            <div x-show="open"
                 x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="open = false"
                 class="fixed inset-0 z-40 bg-gray-900/50 dark:bg-black/60" aria-hidden="true"></div>
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                 class="fixed inset-y-0 right-0 z-50 flex w-full flex-col bg-white shadow-xl sm:w-96 dark:bg-gray-950">
                <livewire:storefront.cart-drawer />
            </div>
        </div>
    </div>

    {{-- Search modal (spec 04 §11.1) --}}
    <livewire:storefront.search.modal />

    @fluxScripts
</body>
</html>
