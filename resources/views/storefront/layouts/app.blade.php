{{--
    Storefront master layout.

    Used by Livewire full-page components (rendered through the component
    slot) and by plain views such as error pages (rendered through the
    "content" section). Supports both via @yield + {{ $slot ?? '' }}.
--}}
@php
    $store = $currentStore ?? (app()->bound('current_store') ? app('current_store') : null);
    $storeName = $store?->name ?? config('app.name');

    $defaults = [
        'primary_color' => '#2563eb',
        'secondary_color' => '#4f46e5',
        'accent_color' => '#0ea5e9',
        'dark_mode' => 'system',
        'sticky_header' => true,
        'show_announcement_bar' => false,
        'announcement_text' => '',
        'announcement_link' => null,
        'announcement_bg_color' => '#111827',
        'logo_url' => null,
        'footer_text' => null,
        'footer_columns' => 4,
        'contact_email' => null,
        'store_address' => null,
        'social_facebook' => null,
        'social_instagram' => null,
        'social_twitter' => null,
        'social_tiktok' => null,
        'social_youtube' => null,
        'payment_icons' => true,
        'meta_description' => '',
    ];

    $settings = $defaults;

    if ($store) {
        $theme = $store->themes()->where('status', 'published')->first();
        $settings = array_replace($defaults, $theme?->settings?->settings_json ?? []);
    }

    $darkMode = $settings['dark_mode'];
    $mainNav = [];
    $footerNav = [];

    if ($store) {
        $navigationService = app(\App\Services\NavigationService::class);
        $mainMenu = \App\Models\NavigationMenu::where('store_id', $store->id)->where('handle', 'main-menu')->first();
        $mainNav = $mainMenu ? $navigationService->buildTree($mainMenu) : [];
        $footerMenu = \App\Models\NavigationMenu::where('store_id', $store->id)->where('handle', 'footer-menu')->first();
        $footerNav = $footerMenu ? $navigationService->buildTree($footerMenu) : [];
    }

    $cartId = session('cart_id');
    $cartCount = 0;

    if ($cartId) {
        $cartModel = \App\Models\Cart::find($cartId);
        $cartCount = (int) ($cartModel?->lines()->sum('quantity') ?? 0);
    }

    $pageTitle = $title ?? $storeName;
    $metaDescription = $metaDescription ?? $settings['meta_description'] ?? '';
    $contactEmail = $settings['contact_email']
        ?? ($store?->settings?->settings_json['contact_email'] ?? null);
    $customer = auth('customer')->user();
    $og = $og ?? null;
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="scroll-smooth"
    x-data="storefrontApp({ dark: @js($darkMode), cartCount: {{ $cartCount }} })"
    :class="isDark ? 'dark' : ''"
>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ $pageTitle }}</title>

    @if ($metaDescription !== '')
        <meta name="description" content="{{ $metaDescription }}" />
    @endif

    @if (is_array($og))
        <meta property="og:title" content="{{ $og['title'] ?? $pageTitle }}" />
        <meta property="og:description" content="{{ $og['description'] ?? $metaDescription }}" />
        @if (! empty($og['image']))
            <meta property="og:image" content="{{ $og['image'] }}" />
        @endif
        <meta property="og:type" content="{{ $og['type'] ?? 'website' }}" />
        <meta property="og:url" content="{{ url()->current() }}" />
        @if (! empty($og['price_amount']))
            <meta property="product:price:amount" content="{{ number_format((int) $og['price_amount'] / 100, 2, '.', ',') }}" />
            <meta property="product:price:currency" content="{{ $og['price_currency'] ?? ($store?->default_currency ?? 'EUR') }}" />
        @endif
    @endif

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
    </style>

    {{-- Apply the correct color scheme before first paint to avoid a flash. --}}
    <script>
        (function () {
            try {
                var preference = @json($darkMode);
                var stored = localStorage.getItem('theme');
                var dark = stored ? stored === 'dark' : (preference === 'dark' || (preference !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches));
                if (dark) document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>

    <script>
        window.storefrontApp = function (options) {
            return {
                isDark: false,
                cartCount: options.cartCount || 0,

                init() {
                    var preference = options.dark || 'system';
                    var apply = () => {
                        var stored = localStorage.getItem('theme');
                        var resolved = stored || preference;
                        this.isDark = resolved === 'dark' || (resolved === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                        document.documentElement.classList.toggle('dark', this.isDark);
                    };
                    apply();

                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                        if ((localStorage.getItem('theme') || preference) === 'system') apply();
                    });

                    window.addEventListener('cart-updated', (event) => {
                        if (event.detail && typeof event.detail.itemCount !== 'undefined') {
                            this.cartCount = event.detail.itemCount;
                        }
                    });
                },

                toggleDark() {
                    this.isDark = !this.isDark;
                    localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
                    document.documentElement.classList.toggle('dark', this.isDark);
                },

                openCart() {
                    this.$dispatch('open-cart-drawer');
                },

                openSearch() {
                    this.$dispatch('open-search-modal');
                },
            };
        };
    </script>
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <a
        href="#main-content"
        class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[70] focus:rounded-lg focus:bg-zinc-900 focus:px-4 focus:py-2.5 focus:text-sm focus:font-medium focus:text-white focus:shadow-xl dark:focus:bg-white dark:focus:text-zinc-900"
    >
        Skip to main content
    </a>

    <livewire:storefront.search.modal />

    @include('storefront.partials.announcement-bar', ['settings' => $settings])
    @include('storefront.partials.header', [
        'storeName' => $storeName,
        'settings' => $settings,
        'mainNav' => $mainNav,
        'customer' => $customer,
    ])

    <main id="main-content" class="flex-1">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    @include('storefront.partials.footer', [
        'storeName' => $storeName,
        'settings' => $settings,
        'footerNav' => $footerNav,
        'contactEmail' => $contactEmail,
        'store' => $store,
    ])

    <livewire:storefront.cart-drawer />
</body>
</html>
