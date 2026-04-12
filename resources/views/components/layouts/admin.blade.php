@php
    /** @var \App\Models\Store|null $currentStore */
    $currentStore = app()->bound('current_store') ? app('current_store') : null;
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Shop Admin' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 p-2" wire:navigate>
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-zinc-900 text-white dark:bg-white dark:text-zinc-900">
                        <flux:icon name="shopping-bag" class="size-5" />
                    </div>
                    <div class="flex min-w-0 flex-col leading-tight">
                        <span class="truncate text-sm font-semibold">Shop Admin</span>
                        @if ($currentStore !== null)
                            <span class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $currentStore->name }}</span>
                        @endif
                    </div>
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group heading="Catalog" class="grid">
                    <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                        Dashboard
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cube" :href="route('admin.products.index')" :current="request()->routeIs('admin.products.*')" wire:navigate>
                        Products
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="folder" :href="route('admin.collections.index')" :current="request()->routeIs('admin.collections.*')" wire:navigate>
                        Collections
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('admin.customers.index')" :current="request()->routeIs('admin.customers.*')" wire:navigate>
                        Customers
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="tag" :href="route('admin.discounts.index')" :current="request()->routeIs('admin.discounts.*')" wire:navigate>
                        Discounts
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Orders" class="grid">
                    <flux:sidebar.item icon="shopping-cart" :href="route('admin.orders.index')" :current="request()->routeIs('admin.orders.*')" wire:navigate>
                        Orders
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            @if ($user !== null)
                <flux:dropdown position="top" align="start" class="mt-auto">
                    <flux:sidebar.profile :name="$user->name" :initials="$user->initials()" icon-trailing="chevrons-up-down" />
                    <flux:menu>
                        <div class="p-2 text-sm">
                            <div class="font-semibold">{{ $user->name }}</div>
                            <div class="text-zinc-500 dark:text-zinc-400">{{ $user->email }}</div>
                        </div>
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" data-test="admin-logout-button">
                                Log out
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @endif
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            @if ($user !== null)
                <flux:dropdown position="top" align="end">
                    <flux:profile :initials="$user->initials()" icon-trailing="chevron-down" />
                    <flux:menu>
                        <div class="p-2 text-sm">
                            <div class="font-semibold">{{ $user->name }}</div>
                            <div class="text-zinc-500 dark:text-zinc-400">{{ $user->email }}</div>
                        </div>
                        <flux:menu.separator />
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle">
                                Log out
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @endif
        </flux:header>

        <flux:main>
            {{ $slot }}
        </flux:main>

        @livewireScripts
        @fluxScripts
    </body>
</html>
