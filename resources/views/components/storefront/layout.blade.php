<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? app('current_store')->name }} - {{ app('current_store')->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:shadow">Skip to main content</a>

    <div class="bg-zinc-950 px-4 py-2 text-center text-sm text-white dark:bg-white dark:text-zinc-950">
        {{ app('current_store')->settings?->settings_json['announcement'] ?? 'Free shipping over 75 EUR' }}
    </div>

    <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4">
            <a href="{{ route('home') }}" class="text-lg font-bold tracking-normal">{{ app('current_store')->name }}</a>
            <div class="hidden items-center gap-6 lg:flex">
                <a class="text-sm hover:text-zinc-600" href="{{ route('collections.index') }}">Collections</a>
                <a class="text-sm hover:text-zinc-600" href="{{ route('collections.show', 't-shirts') }}">T-Shirts</a>
                <a class="text-sm hover:text-zinc-600" href="{{ route('search') }}">Search</a>
                <a class="text-sm hover:text-zinc-600" href="{{ route('pages.show', 'about') }}">About</a>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('search') }}" aria-label="Search" class="rounded-md border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800">Search</a>
                <a href="{{ route('cart.show') }}" aria-label="Cart" class="rounded-md bg-zinc-950 px-3 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-950">Cart</a>
                <a href="{{ auth('customer')->check() ? route('account.dashboard') : route('account.login') }}" class="hidden rounded-md border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 sm:block">Account</a>
            </div>
        </nav>
    </header>

    <main id="main">
        @if(session('status'))
            <div class="mx-auto mt-4 max-w-7xl px-4">
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            </div>
        @endif
        @if($errors->any())
            <div class="mx-auto mt-4 max-w-7xl px-4">
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 md:grid-cols-3">
            <div>
                <h2 class="font-semibold">{{ app('current_store')->name }}</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Complete storefront demo with cart, checkout, accounts, and admin operations.</p>
            </div>
            <div class="grid gap-2 text-sm">
                <a href="{{ route('collections.index') }}">Collections</a>
                <a href="{{ route('search') }}">Search</a>
                <a href="{{ route('pages.show', 'about') }}">About</a>
            </div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                (c) {{ now()->year }} {{ app('current_store')->name }}. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>

