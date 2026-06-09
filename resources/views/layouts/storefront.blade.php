@php
    $themeSettings = app(\App\Services\ThemeSettingsService::class)->all();
    $mainMenu = app(\App\Services\NavigationService::class)->tree('main-menu');
    $footerMenu = app(\App\Services\NavigationService::class)->tree('footer-menu');
    $storeName = $currentStore->name ?? config('app.name');
    $cartItemCount = isset($currentStore)
        ? (app(\App\Services\CartService::class)->findFor($currentStore, auth('customer')->user())?->itemCount() ?? 0)
        : 0;
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="scroll-smooth"
    style="--sf-primary: {{ $themeSettings['primary_color'] }}; --sf-secondary: {{ $themeSettings['secondary_color'] }};"
>
    <head>
        @include('partials.head', ['title' => isset($title) && filled($title) ? $title.' - '.$storeName : $storeName])
        @isset($metaDescription)
            <meta name="description" content="{{ $metaDescription }}" />
        @endisset
    </head>
    <body class="flex min-h-screen flex-col bg-white text-zinc-700 antialiased dark:bg-zinc-950 dark:text-zinc-300">
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-zinc-900 focus:shadow-lg focus:ring-2 focus:ring-blue-600 dark:focus:bg-zinc-900 dark:focus:text-white"
        >
            {{ __('Skip to main content') }}
        </a>

        @include('storefront.partials.announcement-bar')

        @include('storefront.partials.header')

        <main id="main-content" tabindex="-1" class="flex-1 focus:outline-none">
            {{ $slot }}
        </main>

        @include('storefront.partials.footer')

        <livewire:storefront.cart-drawer />

        @fluxScripts
    </body>
</html>
