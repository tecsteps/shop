@php
    $store = isset($currentStore) ? $currentStore : null;
    $themeSettings = $settings ?? data_get($store, 'theme_settings', []);
    $storeName = data_get($store, 'name', config('app.name', 'Store'));
    $darkMode = data_get($themeSettings, 'dark_mode', 'system');
    $mainMenu = data_get($themeSettings, 'navigation.main-menu', data_get($themeSettings, 'main_menu', []));
    $footerMenu = data_get($themeSettings, 'navigation.footer-menu', data_get($themeSettings, 'footer_menu', []));
    $logoUrl = data_get($themeSettings, 'logo_url', data_get($store, 'logo_url'));
    $announcementEnabled = (bool) data_get($themeSettings, 'announcement.enabled', false);
    $announcementText = data_get($themeSettings, 'announcement.text', 'Free shipping on orders over 75 EUR');
    $announcementUrl = data_get($themeSettings, 'announcement.url');
    $cartLines = data_get($cart ?? null, 'lines') ?? data_get($cart ?? null, 'items', []);
    $cartItemCount = (int) data_get($cart ?? null, 'item_count', data_get($cart ?? null, 'items_count', is_countable($cartLines) ? count($cartLines) : 0));
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => $darkMode === 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="{{ $darkMode === 'light' ? 'light' : ($darkMode === 'dark' ? 'dark' : 'light dark') }}">
    <meta name="description" content="@yield('meta_description', data_get($themeSettings, 'seo.home_description', 'Discover thoughtful pieces selected by '.$storeName.'.'))">
    <meta name="theme-color" content="{{ data_get($themeSettings, 'colors.primary', '#1d4ed8') }}">
    <title>@hasSection('title')@yield('title') · @endif{{ $storeName }}</title>
    @yield('social_meta')
    <link rel="preconnect" href="https://fonts.bunny.net">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script>
        (() => {
            const mode = @js($darkMode);
            const saved = localStorage.getItem('storefront-theme');
            const dark = mode === 'dark' || (mode !== 'light' && (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)));
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    <script type="application/ld+json">{!! json_encode([chr(64).'context' => 'https://schema.org', chr(64).'type' => 'Organization', 'name' => $storeName, 'url' => url('/'), 'logo' => $logoUrl, 'email' => data_get($store, 'email')], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @yield('structured_data')
</head>
<body data-storefront-theme class="min-h-screen bg-white font-sans text-stone-950 antialiased transition-colors dark:bg-stone-950 dark:text-stone-50" style="--store-primary: {{ data_get($themeSettings, 'colors.primary', '#1d4ed8') }}; --store-secondary: {{ data_get($themeSettings, 'colors.secondary', '#292524') }}; --store-accent: {{ data_get($themeSettings, 'colors.accent', '#0f766e') }};">
    <a href="#main-content" class="storefront-skip-link">Skip to main content</a>

    @if ($announcementEnabled)
        <div x-data="{ visible: localStorage.getItem('storefront-announcement-dismissed') !== 'true' }" x-show="visible" x-cloak class="bg-stone-950 px-4 py-2.5 text-center text-sm text-white dark:bg-stone-100 dark:text-stone-950">
            <div class="mx-auto flex max-w-7xl items-center justify-center gap-3 pr-9">
                <p class="leading-5">
                    @if ($announcementUrl)
                        <a href="{{ $announcementUrl }}" class="underline decoration-white/50 underline-offset-4 hover:decoration-white dark:decoration-stone-950/50 dark:hover:decoration-stone-950">{{ $announcementText }}</a>
                    @else
                        {{ $announcementText }}
                    @endif
                </p>
                <button type="button" x-on:click="visible = false; localStorage.setItem('storefront-announcement-dismissed', 'true')" class="absolute right-4 inline-flex size-9 items-center justify-center rounded-full text-white/75 hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white dark:text-stone-700 dark:hover:bg-black/10 dark:hover:text-stone-950 dark:focus-visible:outline-stone-950" aria-label="Dismiss announcement">
                    @include('storefront.components.icon', ['name' => 'close', 'class' => 'size-4'])
                </button>
            </div>
        </div>
    @endif

    <header @if (data_get($themeSettings, 'sticky_header', true)) class="sticky top-0 z-40 border-b border-stone-200/80 bg-white/95 backdrop-blur dark:border-stone-800 dark:bg-stone-950/90" @else class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-950" @endif>
        <div class="mx-auto grid min-h-16 max-w-7xl grid-cols-[1fr_auto_1fr] items-center gap-3 px-4 sm:px-6 lg:flex lg:min-h-20 lg:gap-10 lg:px-8">
            <div x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="lg:hidden">
                <button type="button" x-on:click="open = true" x-bind:aria-expanded="open.toString()" aria-controls="storefront-mobile-navigation" aria-label="Open navigation menu" class="flex size-11 items-center justify-center rounded-full text-stone-800 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:text-stone-100 dark:hover:bg-stone-800">
                    @include('storefront.components.icon', ['name' => 'menu', 'class' => 'size-5'])
                </button>
                <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-50" role="presentation">
                    <button type="button" x-on:click="open = false" class="absolute inset-0 bg-stone-950/50" aria-label="Close navigation menu"></button>
                    <nav id="storefront-mobile-navigation" x-trap.noscroll="open" x-on:keydown.escape.window="open = false" role="dialog" aria-modal="true" aria-label="Mobile navigation" class="absolute inset-y-0 left-0 flex w-[min(23rem,88vw)] flex-col bg-white shadow-2xl dark:bg-stone-950">
                        <div class="flex min-h-16 items-center justify-between gap-4 border-b border-stone-200 px-5 dark:border-stone-800"><span class="truncate text-lg font-semibold">{{ $storeName }}</span><button type="button" x-on:click="open = false" autofocus class="inline-flex size-11 items-center justify-center rounded-full hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-blue-700 dark:hover:bg-stone-900" aria-label="Close navigation menu">@include('storefront.components.icon', ['name' => 'close', 'class' => 'size-5'])</button></div>
                        <ul class="flex-1 space-y-1 overflow-y-auto p-4">
                            @forelse ($mainMenu as $item)
                                @php($itemLabel = data_get($item, 'label', data_get($item, 'title', 'Browse')))
                                <li>
                                    @if (!empty(data_get($item, 'children', [])))
                                        <details class="group/nav">
                                            <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between rounded-xl px-3 text-sm font-medium hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-blue-700 dark:hover:bg-stone-900 [&::-webkit-details-marker]:hidden">{{ $itemLabel }}@include('storefront.components.icon', ['name' => 'chevron-down', 'class' => 'size-4 transition group-open/nav:rotate-180'])</summary>
                                            <ul class="ml-4 border-l border-stone-200 py-1 pl-3 dark:border-stone-800"><li><a href="{{ data_get($item, 'url', '#') }}" class="flex min-h-11 items-center rounded-lg px-3 text-sm text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-900">View all {{ $itemLabel }}</a></li>@foreach (data_get($item, 'children', []) as $child)<li><a href="{{ data_get($child, 'url', '#') }}" class="flex min-h-11 items-center rounded-lg px-3 text-sm text-stone-600 hover:bg-stone-100 dark:text-stone-300 dark:hover:bg-stone-900">{{ data_get($child, 'label', data_get($child, 'title', 'View')) }}</a></li>@endforeach</ul>
                                        </details>
                                    @else
                                        <a href="{{ data_get($item, 'url', '#') }}" class="flex min-h-12 items-center rounded-xl px-3 text-sm font-medium hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-blue-700 dark:hover:bg-stone-900">{{ $itemLabel }}</a>
                                    @endif
                                </li>
                            @empty
                                <li><a href="/collections" class="flex min-h-12 items-center rounded-xl px-3 text-sm font-medium hover:bg-stone-100 dark:hover:bg-stone-900">Shop all</a></li>
                            @endforelse
                        </ul>
                        <a href="/account" class="flex min-h-14 items-center gap-3 border-t border-stone-200 px-5 text-sm font-semibold hover:bg-stone-50 dark:border-stone-800 dark:hover:bg-stone-900">@include('storefront.components.icon', ['name' => 'user', 'class' => 'size-5']) My account</a>
                    </nav>
                </div>
            </div>

            <a href="/" class="col-start-2 inline-flex max-w-[min(48vw,15rem)] items-center justify-center justify-self-center text-center lg:col-start-auto lg:justify-start" aria-label="{{ $storeName }} home">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="max-h-8 w-auto object-contain lg:max-h-10">
                @else
                    <span class="truncate text-lg font-semibold tracking-tight sm:text-xl">{{ $storeName }}</span>
                @endif
            </a>

            <nav aria-label="Main navigation" class="hidden flex-1 items-center justify-center gap-1 lg:flex">
                @forelse ($mainMenu as $item)
                    @php($itemChildren = data_get($item, 'children', []))
                    <div class="group relative">
                        <a href="{{ data_get($item, 'url', '#') }}" class="inline-flex min-h-11 items-center gap-1 rounded-full px-4 text-sm font-medium text-stone-700 hover:bg-stone-100 hover:text-stone-950 focus-visible:outline-2 focus-visible:outline-blue-700 dark:text-stone-200 dark:hover:bg-stone-800 dark:hover:text-white">
                            {{ data_get($item, 'label', data_get($item, 'title', 'Browse')) }}
                            @if (!empty($itemChildren))
                                @include('storefront.components.icon', ['name' => 'chevron-down', 'class' => 'size-3.5'])
                            @endif
                        </a>
                        @if (!empty($itemChildren))
                            <ul class="invisible absolute left-0 top-full z-50 min-w-52 translate-y-2 rounded-2xl border border-stone-200 bg-white p-2 opacity-0 shadow-xl transition group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100 dark:border-stone-700 dark:bg-stone-900">
                                @foreach ($itemChildren as $child)
                                    <li><a href="{{ data_get($child, 'url', '#') }}" class="flex min-h-11 items-center rounded-xl px-3 text-sm text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800">{{ data_get($child, 'label', data_get($child, 'title', 'View')) }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @empty
                    <a href="/collections" class="inline-flex min-h-11 items-center rounded-full px-4 text-sm font-medium text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800">Shop all</a>
                @endforelse
            </nav>

            <div class="col-start-3 flex items-center justify-self-end gap-1 sm:gap-2 lg:ml-auto">
                <button type="button" wire:click="$dispatch('open-search-modal')" class="inline-flex size-11 items-center justify-center rounded-full text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:text-stone-200 dark:hover:bg-stone-800" aria-label="Search products">
                    @include('storefront.components.icon', ['name' => 'search', 'class' => 'size-5'])
                </button>
                <a href="/account" class="hidden size-11 items-center justify-center rounded-full text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:text-stone-200 dark:hover:bg-stone-800 sm:inline-flex" aria-label="My account">
                    @include('storefront.components.icon', ['name' => 'user', 'class' => 'size-5'])
                </a>
                <button type="button" wire:click="$dispatch('open-cart-drawer')" class="relative inline-flex size-11 items-center justify-center rounded-full text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:text-stone-200 dark:hover:bg-stone-800" aria-label="Open shopping cart, {{ $cartItemCount }} items">
                    @include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-5'])
                    <span data-cart-count class="{{ $cartItemCount > 0 ? '' : 'hidden' }} absolute right-0.5 top-0.5 inline-flex min-w-5 items-center justify-center rounded-full bg-blue-700 px-1 text-[10px] font-semibold leading-5 text-white dark:bg-blue-400 dark:text-stone-950" aria-live="polite">{{ $cartItemCount }}</span>
                </button>
                <button type="button" x-data x-on:click="const html = document.documentElement; const dark = !html.classList.contains('dark'); html.classList.toggle('dark', dark); localStorage.setItem('storefront-theme', dark ? 'dark' : 'light')" class="hidden size-11 items-center justify-center rounded-full text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 sm:inline-flex dark:text-stone-200 dark:hover:bg-stone-800" aria-label="Toggle dark mode">
                    <span class="dark:hidden" aria-hidden="true">☾</span><span class="hidden dark:inline" aria-hidden="true">☀</span>
                </button>
            </div>
        </div>
    </header>

    <main id="main-content" tabindex="-1" class="min-h-[55vh] focus:outline-none">
        @if (session('status') || session('success') || session('error'))
            <div class="mx-auto max-w-7xl px-4 pt-5 sm:px-6 lg:px-8" role="status" aria-live="polite">
                @foreach (['status' => 'success', 'success' => 'success', 'error' => 'error'] as $flashKey => $flashType)
                    @if (session($flashKey))
                        <div class="rounded-2xl border px-4 py-3 text-sm {{ $flashType === 'error' ? 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-100' : 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100' }}">{{ session($flashKey) }}</div>
                    @endif
                @endforeach
            </div>
        @endif
        @if (isset($slot))
            {{ $slot }}
        @else
            @yield('content')
        @endif
    </main>

    <footer class="border-t border-stone-200 bg-stone-50 dark:border-stone-800 dark:bg-stone-900">
        @if (class_exists(\App\Livewire\Storefront\NewsletterSignup::class))
            @livewire('storefront.newsletter-signup')
        @endif
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <div class="grid grid-cols-2 gap-x-8 gap-y-10 md:grid-cols-3 lg:grid-cols-4">
                @forelse (array_slice(is_array($footerMenu) ? $footerMenu : [], 0, 3) as $column)
                    <div>
                        <h2 class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-950 dark:text-white">{{ data_get($column, 'label', data_get($column, 'title', 'Explore')) }}</h2>
                        <ul class="mt-4 grid gap-3">
                            @foreach (data_get($column, 'children', data_get($column, 'links', [])) as $link)
                                <li><a href="{{ data_get($link, 'url', '#') }}" class="text-sm text-stone-600 underline-offset-4 hover:text-stone-950 hover:underline dark:text-stone-300 dark:hover:text-white">{{ data_get($link, 'label', data_get($link, 'title', 'Learn more')) }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <div>
                        <h2 class="text-xs font-semibold uppercase tracking-[0.16em]">Explore</h2>
                        <ul class="mt-4 grid gap-3 text-sm text-stone-600 dark:text-stone-300"><li><a href="/collections">Shop all</a></li><li><a href="/pages/about">About us</a></li><li><a href="/account">My account</a></li></ul>
                    </div>
                @endforelse
                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-950 dark:text-white">{{ $storeName }}</h2>
                    @if (data_get($store, 'address'))
                        <p class="mt-4 max-w-xs text-sm leading-6 text-stone-600 dark:text-stone-300">{{ data_get($store, 'address') }}</p>
                    @endif
                    @if (data_get($store, 'email'))
                        <a href="mailto:{{ data_get($store, 'email') }}" class="mt-3 inline-block text-sm text-stone-600 underline-offset-4 hover:text-stone-950 hover:underline dark:text-stone-300 dark:hover:text-white">{{ data_get($store, 'email') }}</a>
                    @endif
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'twitter' => 'X', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'] as $socialKey => $socialLabel)
                            @if (data_get($themeSettings, 'social.'.$socialKey))
                                <a href="{{ data_get($themeSettings, 'social.'.$socialKey) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center rounded-full border border-stone-300 px-3 text-xs font-medium text-stone-600 hover:border-stone-900 hover:text-stone-950 dark:border-stone-700 dark:text-stone-300 dark:hover:border-white dark:hover:text-white" aria-label="Visit {{ $storeName }} on {{ $socialLabel }}">{{ $socialLabel }}</a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="mt-12 flex flex-col gap-5 border-t border-stone-200 pt-6 sm:flex-row sm:items-center sm:justify-between dark:border-stone-800">
                <p class="text-xs text-stone-500 dark:text-stone-400">© {{ now()->year }} {{ $storeName }}. All rights reserved.</p>
                <div class="flex flex-wrap items-center gap-2" aria-label="Accepted payment methods">
                    @foreach (data_get($themeSettings, 'payment_methods', ['Visa', 'Mastercard', 'PayPal']) as $paymentMethod)
                        <span class="rounded-md border border-stone-200 bg-white px-2 py-1 text-[10px] font-semibold tracking-wide text-stone-500 dark:border-stone-700 dark:bg-stone-950 dark:text-stone-400">{{ data_get($paymentMethod, 'label', $paymentMethod) }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </footer>

    @livewire('storefront.cart-drawer')
    @livewire('storefront.search.modal')
    @livewireScripts
    @stack('scripts')
</body>
</html>
