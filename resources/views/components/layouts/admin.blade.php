@props(['title' => 'Admin'])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | {{ $currentStore->name ?? 'Admin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-full bg-zinc-50 antialiased dark:bg-zinc-900">
    <flux:sidebar sticky stashable class="border-r border-zinc-200 dark:border-zinc-700" data-test="admin-sidebar">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-4 py-4" wire:navigate>
            <span class="inline-flex size-8 items-center justify-center rounded-lg bg-zinc-900 text-white dark:bg-white dark:text-zinc-900">
                <span class="text-sm font-bold">S</span>
            </span>
            <div class="flex flex-col">
                <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $currentStore->name }}</span>
                <span class="text-xs text-zinc-500">Admin</span>
            </div>
        </a>

        <flux:navlist variant="outline">
            <flux:navlist.item href="{{ route('admin.dashboard') }}" icon="home" :current="request()->routeIs('admin.dashboard')" wire:navigate>Dashboard</flux:navlist.item>
            <flux:navlist.group heading="Orders">
                <flux:navlist.item href="{{ route('admin.orders.index') }}" icon="shopping-bag" :current="request()->routeIs('admin.orders.*')" wire:navigate>Orders</flux:navlist.item>
                <flux:navlist.item href="{{ route('admin.customers.index') }}" icon="user-group" :current="request()->routeIs('admin.customers.*')" wire:navigate>Customers</flux:navlist.item>
            </flux:navlist.group>
            <flux:navlist.group heading="Catalog">
                <flux:navlist.item href="{{ route('admin.products.index') }}" icon="cube" :current="request()->routeIs('admin.products.*')" wire:navigate>Products</flux:navlist.item>
                <flux:navlist.item href="{{ route('admin.collections.index') }}" icon="squares-2x2" :current="request()->routeIs('admin.collections.*')" wire:navigate>Collections</flux:navlist.item>
                <flux:navlist.item href="{{ route('admin.inventory.index') }}" icon="archive-box" :current="request()->routeIs('admin.inventory.*')" wire:navigate>Inventory</flux:navlist.item>
            </flux:navlist.group>
            <flux:navlist.group heading="Marketing">
                <flux:navlist.item href="{{ route('admin.discounts.index') }}" icon="ticket" :current="request()->routeIs('admin.discounts.*')" wire:navigate>Discounts</flux:navlist.item>
            </flux:navlist.group>
            <flux:navlist.group heading="Online store">
                <flux:navlist.item href="{{ route('admin.pages.index') }}" icon="document-text" :current="request()->routeIs('admin.pages.*')" wire:navigate>Pages</flux:navlist.item>
                <flux:navlist.item href="{{ route('admin.navigation.index') }}" icon="bars-3" :current="request()->routeIs('admin.navigation.*')" wire:navigate>Navigation</flux:navlist.item>
            </flux:navlist.group>
            <flux:navlist.group heading="Settings">
                <flux:navlist.item href="{{ route('admin.settings.shipping') }}" icon="truck" :current="request()->routeIs('admin.settings.shipping')" wire:navigate>Shipping</flux:navlist.item>
                <flux:navlist.item href="{{ route('admin.settings.taxes') }}" icon="calculator" :current="request()->routeIs('admin.settings.taxes')" wire:navigate>Taxes</flux:navlist.item>
                <flux:navlist.item href="{{ route('admin.settings.general') }}" icon="cog-6-tooth" :current="request()->routeIs('admin.settings.general')" wire:navigate>General</flux:navlist.item>
            </flux:navlist.group>
        </flux:navlist>

        <flux:spacer />

        <flux:dropdown position="top" align="start">
            <flux:profile
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                icon-trailing="chevrons-up-down"
            />
            <flux:menu>
                <flux:menu.item icon="arrow-top-right-on-square" href="/" target="_blank">View storefront</flux:menu.item>
                <flux:menu.separator />
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">Log out</flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <flux:main>
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
        </flux:header>
        <div class="mx-auto w-full max-w-7xl p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </div>
    </flux:main>
    @fluxScripts
    @livewireScripts
</body>
</html>
