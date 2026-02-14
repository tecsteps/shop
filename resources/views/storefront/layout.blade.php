<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', ($currentStore->name ?? config('app.name')).' Storefront')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:rounded-md focus:bg-white focus:px-3 focus:py-2 focus:shadow">
        Skip to main content
    </a>

    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <div>
                <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight text-slate-900">
                    {{ $currentStore->name ?? config('app.name') }}
                </a>
            </div>

            <nav class="flex flex-wrap items-center gap-4 text-sm font-medium text-slate-700">
                <a href="{{ route('home') }}" class="hover:text-slate-900">Home</a>
                <a href="{{ route('storefront.collections.index') }}" class="hover:text-slate-900">Collections</a>
                <a href="{{ route('storefront.search.index') }}" class="hover:text-slate-900">Search</a>
                <a href="{{ route('storefront.cart.show') }}" class="hover:text-slate-900">Cart</a>
                <a href="{{ auth('customer')->check() ? route('account.dashboard') : route('account.login') }}" class="hover:text-slate-900">Account</a>
            </nav>
        </div>
    </header>

    <main id="main-content" class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
