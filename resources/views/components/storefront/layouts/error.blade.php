<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-white text-zinc-700 antialiased dark:bg-zinc-950 dark:text-zinc-300">
    @php
        $store = app()->bound('current_store') ? app('current_store') : null;
        $storeName = $store?->name ?? config('app.name');
    @endphp

    {{-- Header --}}
    <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <a href="/" class="flex items-center text-xl font-bold text-zinc-900 dark:text-white">
                    {{ $storeName }}
                </a>
                <nav class="hidden lg:flex lg:items-center lg:gap-6" aria-label="Main navigation">
                    <a href="/collections" class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                        Collections
                    </a>
                </nav>
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
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Shop</h3>
                    <ul class="mt-4 space-y-2">
                        <li><a href="/collections" class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Collections</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Info</h3>
                    <ul class="mt-4 space-y-2">
                        <li><a href="/pages/about" class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">About</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $storeName }}</h3>
                    <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">Quality fashion for everyone.</p>
                </div>
            </div>
            <div class="mt-8 border-t border-zinc-200 pt-8 dark:border-zinc-700">
                <p class="text-xs text-zinc-400">&copy; {{ date('Y') }} {{ $storeName }}. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
