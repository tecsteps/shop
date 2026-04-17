@props(['title' => null])

@php
    $currentStore = app()->bound('current_store') ? app('current_store') : null;
    $user = auth()->user();
    $accessibleStores = $user
        ? $user->stores()->get()
        : collect();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title ? $title.' | Admin' : 'Admin' }}{{ $currentStore ? ' - '.$currentStore->name : '' }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="min-h-screen bg-neutral-50 text-neutral-900 antialiased dark:bg-neutral-950 dark:text-neutral-100">
    <div class="flex min-h-screen">
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900 lg:flex">
            <div class="flex items-center gap-2 border-b border-neutral-200 px-4 py-4 dark:border-neutral-800">
                <flux:brand href="{{ url('/admin') }}" name="Shop Admin" />
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-2 py-4 text-sm">
                <div>
                    <a href="{{ url('/admin') }}" class="flex items-center gap-2 rounded-md px-3 py-2 font-medium text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800 {{ request()->is('admin') || request()->is('admin/') ? 'bg-neutral-100 dark:bg-neutral-800' : '' }}">
                        <flux:icon name="chart-bar" variant="mini" />
                        Dashboard
                    </a>
                </div>

                <div class="space-y-1">
                    <p class="px-3 text-xs font-semibold uppercase tracking-wide text-neutral-400">Catalog</p>
                    <a href="{{ url('/admin/products') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800 {{ request()->is('admin/products*') ? 'bg-neutral-100 font-medium dark:bg-neutral-800' : '' }}">
                        <flux:icon name="cube" variant="mini" />
                        Products
                    </a>
                    <a href="{{ url('/admin/collections') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800 {{ request()->is('admin/collections*') ? 'bg-neutral-100 font-medium dark:bg-neutral-800' : '' }}">
                        <flux:icon name="rectangle-stack" variant="mini" />
                        Collections
                    </a>
                </div>

                <div class="space-y-1">
                    <p class="px-3 text-xs font-semibold uppercase tracking-wide text-neutral-400">Sales</p>
                    <a href="{{ url('/admin/orders') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800 {{ request()->is('admin/orders*') ? 'bg-neutral-100 font-medium dark:bg-neutral-800' : '' }}">
                        <flux:icon name="shopping-bag" variant="mini" />
                        Orders
                    </a>
                    <a href="{{ url('/admin/customers') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800 {{ request()->is('admin/customers*') ? 'bg-neutral-100 font-medium dark:bg-neutral-800' : '' }}">
                        <flux:icon name="users" variant="mini" />
                        Customers
                    </a>
                    <a href="{{ url('/admin/discounts') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800 {{ request()->is('admin/discounts*') ? 'bg-neutral-100 font-medium dark:bg-neutral-800' : '' }}">
                        <flux:icon name="tag" variant="mini" />
                        Discounts
                    </a>
                </div>

                <div class="space-y-1">
                    <p class="px-3 text-xs font-semibold uppercase tracking-wide text-neutral-400">Content</p>
                    <a href="{{ url('/admin/pages') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800 {{ request()->is('admin/pages*') ? 'bg-neutral-100 font-medium dark:bg-neutral-800' : '' }}">
                        <flux:icon name="document-text" variant="mini" />
                        Pages
                    </a>
                    <a href="{{ url('/admin/themes') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800 {{ request()->is('admin/themes*') ? 'bg-neutral-100 font-medium dark:bg-neutral-800' : '' }}">
                        <flux:icon name="paint-brush" variant="mini" />
                        Themes
                    </a>
                </div>

                <div class="space-y-1">
                    <p class="px-3 text-xs font-semibold uppercase tracking-wide text-neutral-400">System</p>
                    <a href="{{ url('/admin/settings') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-neutral-800 {{ request()->is('admin/settings*') ? 'bg-neutral-100 font-medium dark:bg-neutral-800' : '' }}">
                        <flux:icon name="cog-6-tooth" variant="mini" />
                        Settings
                    </a>
                </div>
            </nav>
        </aside>

        <div class="flex w-full flex-1 flex-col lg:pl-64">
            <header class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-neutral-200 bg-white px-4 dark:border-neutral-800 dark:bg-neutral-900">
                <div class="flex items-center gap-3">
                    @if ($currentStore)
                        <flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon-trailing="chevron-down">
                                {{ $currentStore->name }}
                            </flux:button>
                            <flux:menu>
                                @foreach ($accessibleStores as $store)
                                    <flux:menu.item
                                        href="{{ url('/admin/switch-store/'.$store->getKey()) }}"
                                        wire:navigate.hover
                                    >
                                        {{ $store->name }}
                                        @if ($store->getKey() === $currentStore->getKey())
                                            <span class="ml-auto text-xs text-neutral-500">current</span>
                                        @endif
                                    </flux:menu.item>
                                @endforeach
                            </flux:menu>
                        </flux:dropdown>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    @if ($user)
                        <flux:dropdown position="bottom" align="end">
                            <flux:profile :initials="$user->initials()" :name="$user->name" icon-trailing="chevron-down" />
                            <flux:menu>
                                <flux:menu.item icon="user" href="{{ url('/settings/profile') }}">Profile</flux:menu.item>
                                <flux:menu.separator />
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                        Log out
                                    </flux:menu.item>
                                </form>
                            </flux:menu>
                        </flux:dropdown>
                    @endif
                </div>
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                @if (session('status'))
                    <flux:callout variant="success" class="mb-4">{{ session('status') }}</flux:callout>
                @endif
                @if (session('error'))
                    <flux:callout variant="danger" class="mb-4">{{ session('error') }}</flux:callout>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @fluxScripts
    @livewireScripts
</body>
</html>
