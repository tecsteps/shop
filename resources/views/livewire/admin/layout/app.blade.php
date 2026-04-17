<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin' }} - {{ app()->bound('current_store') ? app('current_store')->name : config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="hidden w-64 shrink-0 border-r border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 lg:block">
            <div class="flex h-full flex-col">
                {{-- Brand --}}
                <div class="flex h-16 items-center border-b border-zinc-200 px-4 dark:border-zinc-800">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                        <flux:icon name="bolt" class="size-6 text-blue-600" />
                        <span class="text-lg font-bold">
                            {{ app()->bound('current_store') ? app('current_store')->name : 'Admin' }}
                        </span>
                    </a>
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        <flux:icon name="chart-bar" class="size-4" />
                        Dashboard
                    </a>

                    <flux:separator class="my-2" />
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Catalog</p>

                    <a href="{{ route('admin.products.index') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        <flux:icon name="cube" class="size-4" />
                        Products
                    </a>
                    <a href="{{ route('admin.collections.index') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        <flux:icon name="squares-2x2" class="size-4" />
                        Collections
                    </a>

                    <flux:separator class="my-2" />
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Sales</p>

                    <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        <flux:icon name="shopping-cart" class="size-4" />
                        Orders
                    </a>
                    <a href="{{ route('admin.customers.index') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        <flux:icon name="users" class="size-4" />
                        Customers
                    </a>
                    <a href="{{ route('admin.discounts.index') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        <flux:icon name="tag" class="size-4" />
                        Discounts
                    </a>

                    <flux:separator class="my-2" />
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Content</p>

                    <a href="{{ route('admin.pages.index') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        <flux:icon name="document-text" class="size-4" />
                        Pages
                    </a>
                    <a href="{{ route('admin.navigation.index') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        <flux:icon name="bars-3" class="size-4" />
                        Navigation
                    </a>
                    <a href="{{ route('admin.themes.index') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        <flux:icon name="paint-brush" class="size-4" />
                        Themes
                    </a>

                    <flux:separator class="my-2" />
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Configuration</p>

                    <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        <flux:icon name="cog-6-tooth" class="size-4" />
                        Settings
                    </a>
                    <a href="{{ route('admin.analytics.index') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        <flux:icon name="chart-pie" class="size-4" />
                        Analytics
                    </a>
                </nav>
            </div>
        </aside>

        {{-- Main area --}}
        <div class="flex flex-1 flex-col">
            {{-- Top bar --}}
            <header class="flex h-16 items-center justify-between border-b border-zinc-200 bg-white px-6 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                    {{ app()->bound('current_store') ? app('current_store')->name : 'Admin Panel' }}
                </div>
                <div class="flex items-center gap-3">
                    @auth
                        <flux:text class="text-sm">{{ Auth::user()->name }}</flux:text>
                    @endauth
                </div>
            </header>

            {{-- Content --}}
            <main class="flex-1 p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
