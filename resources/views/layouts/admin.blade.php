<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
        <flux:sidebar sticky collapsible="mobile" class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
            <flux:sidebar.header>
                <flux:sidebar.brand href="{{ route('admin.dashboard') }}" name="{{ app()->bound('current_store') ? app('current_store')->name : config('app.name') }}" wire:navigate>
                    <x-slot name="logo" class="size-6 rounded bg-accent text-accent-foreground text-xs font-bold">
                        <flux:icon name="shopping-bag" variant="micro" />
                    </x-slot>
                </flux:sidebar.brand>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="chart-bar-square" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>

                <flux:sidebar.group expandable heading="{{ __('Products') }}" class="grid">
                    <flux:sidebar.item icon="cube" :href="route('admin.products.index')" :current="request()->routeIs('admin.products.*')" wire:navigate>
                        {{ __('Products') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="rectangle-stack" :href="route('admin.collections.index')" :current="request()->routeIs('admin.collections.*')" wire:navigate>
                        {{ __('Collections') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group expandable heading="{{ __('Orders') }}" class="grid">
                    <flux:sidebar.item icon="shopping-bag" :href="route('admin.orders.index')" :current="request()->routeIs('admin.orders.*')" wire:navigate>
                        {{ __('Orders') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group expandable heading="{{ __('Customers') }}" class="grid">
                    <flux:sidebar.item icon="users" :href="route('admin.customers.index')" :current="request()->routeIs('admin.customers.*')" wire:navigate>
                        {{ __('Customers') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group expandable heading="{{ __('Discounts') }}" class="grid">
                    <flux:sidebar.item icon="tag" :href="route('admin.discounts.index')" :current="request()->routeIs('admin.discounts.*')" wire:navigate>
                        {{ __('Discounts') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group expandable heading="{{ __('Content') }}" class="grid">
                    <flux:sidebar.item icon="document-text" :href="route('admin.pages.index')" :current="request()->routeIs('admin.pages.*')" wire:navigate>
                        {{ __('Pages') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="bars-3" :href="route('admin.navigation.index')" :current="request()->routeIs('admin.navigation.*')" wire:navigate>
                        {{ __('Navigation') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group expandable heading="{{ __('Design') }}" class="grid">
                    <flux:sidebar.item icon="paint-brush" :href="route('admin.themes.index')" :current="request()->routeIs('admin.themes.*')" wire:navigate>
                        {{ __('Themes') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="chart-pie" :href="route('admin.analytics.index')" :current="request()->routeIs('admin.analytics.*')" wire:navigate>
                    {{ __('Analytics') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="cog-6-tooth" :href="route('admin.settings.index')" :current="request()->routeIs('admin.settings.*')" wire:navigate>
                    {{ __('Settings') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:sidebar.profile :initials="auth()->user()->initials()" :name="auth()->user()->name" />
                <flux:menu>
                    <flux:menu.item :href="route('admin.settings.index')" icon="cog-6-tooth" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                <flux:menu>
                    <flux:menu.item :href="route('admin.settings.index')" icon="cog-6-tooth" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{-- Toast notifications --}}
        <div
            x-data="{ toasts: [] }"
            x-on:toast.window="toasts.push({ id: Date.now(), ...$event.detail }); setTimeout(() => toasts.shift(), 5000)"
            class="fixed top-4 right-4 z-50 space-y-2"
        >
            <template x-for="toast in toasts" :key="toast.id">
                <div
                    x-transition
                    class="rounded-lg shadow-lg border px-4 py-3 text-sm"
                    :class="{
                        'bg-white dark:bg-zinc-800 border-l-4 border-l-green-500 border-zinc-200 dark:border-zinc-700': toast.type === 'success',
                        'bg-white dark:bg-zinc-800 border-l-4 border-l-red-500 border-zinc-200 dark:border-zinc-700': toast.type === 'error',
                        'bg-white dark:bg-zinc-800 border-l-4 border-l-blue-500 border-zinc-200 dark:border-zinc-700': toast.type === 'info',
                    }"
                    x-text="toast.message"
                ></div>
            </template>
        </div>

        <flux:main>
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>
