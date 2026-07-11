<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head')
        <meta name="description" content="Shop {{ $currentStore->name }} products, collections, and new arrivals.">
        <meta property="og:title" content="{{ $title ?? $currentStore->name }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ request()->url() }}">
        @php($organizationSchema = ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $currentStore->name, 'url' => route('storefront.home')])
        <script type="application/ld+json">{!! json_encode($organizationSchema, JSON_UNESCAPED_SLASHES) !!}</script>
    </head>
    <body class="min-h-screen bg-white font-sans text-zinc-950 antialiased dark:bg-zinc-950 dark:text-zinc-50">
        <a href="#main-content" class="sr-only z-50 rounded bg-white px-4 py-2 text-zinc-950 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to main content</a>

        <div class="bg-zinc-950 px-4 py-2 text-center text-sm text-white dark:bg-white dark:text-zinc-950">Free shipping available with code FREESHIP</div>
        <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-1 px-4 py-4 sm:gap-6 sm:px-6 lg:px-8">
                <a href="{{ route('storefront.home') }}" class="text-xl font-semibold tracking-tight" wire:navigate>{{ $currentStore->name }}</a>
                <nav aria-label="Main navigation" class="hidden items-center gap-6 text-sm font-medium lg:flex">
                    <a href="{{ route('storefront.home') }}" wire:navigate>Home</a>
                    <a href="{{ route('storefront.collections.index') }}" wire:navigate>Collections</a>
                    <a href="{{ route('storefront.search') }}" wire:navigate>Search</a>
                    @auth('customer')
                        <a href="{{ route('storefront.account.dashboard') }}" wire:navigate>Account</a>
                    @else
                        <a href="{{ route('storefront.account.login') }}" wire:navigate>Sign in</a>
                    @endauth
                </nav>
                <div class="flex items-center gap-2">
                    <livewire:storefront.search-modal />
                    <livewire:storefront.cart-drawer />
                    <details class="relative lg:hidden">
                        <summary class="cursor-pointer list-none rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">Menu</summary>
                        <nav aria-label="Mobile navigation" class="absolute right-0 mt-2 grid w-52 gap-1 rounded-xl border border-zinc-200 bg-white p-3 shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                            <a class="rounded px-3 py-2" href="{{ route('storefront.home') }}">Home</a>
                            <a class="rounded px-3 py-2" href="{{ route('storefront.collections.index') }}">Collections</a>
                            <a class="rounded px-3 py-2" href="{{ route('storefront.account.dashboard') }}">Account</a>
                        </nav>
                    </details>
                </div>
            </div>
        </header>

        @if (session('storefront_status'))
            <div role="status" class="mx-auto mt-4 max-w-7xl rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('storefront_status') }}</div>
        @endif

        <main id="main-content" class="min-h-[60vh]">{{ $slot }}</main>

        <footer class="mt-20 border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-3 lg:px-8">
                <div><p class="font-semibold">{{ $currentStore->name }}</p><p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Thoughtfully selected products for everyday life.</p></div>
                <nav aria-label="Footer navigation" class="grid content-start gap-2 text-sm"><a href="{{ route('storefront.collections.index') }}">Shop all collections</a><a href="{{ route('storefront.search') }}">Search</a></nav>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Secure mock checkout. Prices shown in {{ $currentStore->default_currency }}.</p>
            </div>
        </footer>
        @fluxScripts
    </body>
</html>
