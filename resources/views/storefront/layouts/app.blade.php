<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Shop' }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    {{-- Header --}}
    <header class="sticky top-0 z-50 border-b border-zinc-200 bg-white/80 backdrop-blur-lg dark:border-zinc-800 dark:bg-zinc-950/80">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight">
                {{ app()->bound('current_store') ? app('current_store')->name : config('app.name') }}
            </a>

            {{-- Navigation --}}
            <nav class="hidden items-center gap-6 md:flex">
                <a href="{{ route('storefront.collections.index') }}" class="text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                    Collections
                </a>
            </nav>

            {{-- Actions --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('storefront.search') }}" class="rounded-full p-2 text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100" aria-label="Search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </a>

                <a href="{{ route('storefront.cart') }}" class="rounded-full p-2 text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100" aria-label="Cart">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </a>

                <a href="{{ route('storefront.account.login') }}" class="rounded-full p-2 text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100" aria-label="Account">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </a>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @hasSection('content')
            @yield('content')
        @else
            {{ $slot }}
        @endif
    </main>

    {{-- Footer --}}
    <footer class="mt-auto border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Shop</h3>
                    <ul class="mt-4 space-y-2">
                        <li>
                            <a href="{{ route('storefront.collections.index') }}" class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Collections</a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Account</h3>
                    <ul class="mt-4 space-y-2">
                        <li>
                            <a href="{{ route('storefront.account.login') }}" class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Sign In</a>
                        </li>
                        <li>
                            <a href="{{ route('storefront.account.register') }}" class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Create Account</a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Help</h3>
                    <ul class="mt-4 space-y-2">
                        <li>
                            <a href="{{ route('storefront.search') }}" class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Search</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t border-zinc-200 pt-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                &copy; {{ date('Y') }} {{ app()->bound('current_store') ? app('current_store')->name : config('app.name') }}. All rights reserved.
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
