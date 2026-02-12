<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => ($title ?? config('app.name')).' | '.($store->name ?? config('app.name'))])
    <meta name="description" content="{{ $metaDescription ?? 'Shop products online.' }}">
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:shadow">Skip to main content</a>

<div class="relative min-h-screen">
    <header class="border-b border-zinc-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="text-xl font-semibold tracking-tight text-zinc-900" aria-label="Go to storefront home">
                {{ $store->name }}
            </a>

            <nav class="hidden items-center gap-6 md:flex" aria-label="Main navigation">
                @foreach ($menuItems as $item)
                    <a href="{{ $item['url'] }}" class="text-sm font-medium text-zinc-700 hover:text-zinc-900">{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ route('storefront.search.index') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:border-zinc-400 hover:text-zinc-900" aria-label="Open search results page">
                    Search
                </a>
                <a href="{{ route('storefront.cart.show') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:border-zinc-400 hover:text-zinc-900" aria-label="View cart">
                    Cart
                    <span class="ml-1 rounded-full bg-zinc-900 px-2 py-0.5 text-xs font-semibold text-white">{{ $cartItemCount }}</span>
                </a>
                @if ($currentCustomer)
                    <a href="{{ route('storefront.account.dashboard') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:border-zinc-400 hover:text-zinc-900" aria-label="Open account dashboard">
                        Account
                    </a>
                @else
                    <a href="{{ route('storefront.account.login') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:border-zinc-400 hover:text-zinc-900" aria-label="Go to customer login">
                        Log in
                    </a>
                @endif
            </div>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 md:hidden sm:px-6">
            <nav class="flex flex-wrap gap-3" aria-label="Mobile navigation">
                @foreach ($menuItems as $item)
                    <a href="{{ $item['url'] }}" class="text-sm font-medium text-zinc-700 hover:text-zinc-900">{{ $item['label'] }}</a>
                @endforeach
            </nav>
        </div>
    </header>

    <main id="main-content" class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">
                <h2 class="mb-2 text-sm font-semibold text-rose-900">Please check the highlighted fields.</h2>
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-zinc-200 bg-white">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-3 px-4 py-8 text-sm text-zinc-600 sm:px-6 lg:px-8 md:flex-row md:items-center md:justify-between">
            <p>&copy; {{ now()->year }} {{ $store->name }}. All rights reserved.</p>
            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('storefront.collections.index') }}" class="hover:text-zinc-900">Collections</a>
                <a href="{{ route('storefront.search.index') }}" class="hover:text-zinc-900">Search</a>
                <a href="{{ route('storefront.cart.show') }}" class="hover:text-zinc-900">Cart</a>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
