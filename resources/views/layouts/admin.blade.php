<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
        <flux:sidebar sticky collapsible="mobile" class="bg-zinc-50 dark:bg-zinc-900 border-e border-zinc-200 dark:border-zinc-700">
            <flux:sidebar.header>
                <flux:sidebar.brand
                    href="{{ route('admin.dashboard') }}"
                    name="{{ app()->bound('current_store') ? app('current_store')->name : config('app.name') }}"
                    wire:navigate
                />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>

                <flux:sidebar.group expandable heading="Products" class="grid">
                    <flux:sidebar.item :href="route('admin.products.index')" :current="request()->routeIs('admin.products.*')" wire:navigate>
                        {{ __('Products') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item :href="route('admin.collections.index')" :current="request()->routeIs('admin.collections.*')" wire:navigate>
                        {{ __('Collections') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item :href="route('admin.inventory.index')" :current="request()->routeIs('admin.inventory.*')" wire:navigate>
                        {{ __('Inventory') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.item icon="shopping-bag" :href="route('admin.orders.index')" :current="request()->routeIs('admin.orders.*')" wire:navigate>
                    {{ __('Orders') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="users" :href="route('admin.customers.index')" :current="request()->routeIs('admin.customers.*')" wire:navigate>
                    {{ __('Customers') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="tag" :href="route('admin.discounts.index')" :current="request()->routeIs('admin.discounts.*')" wire:navigate>
                    {{ __('Discounts') }}
                </flux:sidebar.item>

                <flux:sidebar.group expandable heading="Content" class="grid">
                    <flux:sidebar.item :href="route('admin.pages.index')" :current="request()->routeIs('admin.pages.*')" wire:navigate>
                        {{ __('Pages') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item :href="route('admin.navigation.index')" :current="request()->routeIs('admin.navigation.*')" wire:navigate>
                        {{ __('Navigation') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item :href="route('admin.themes.index')" :current="request()->routeIs('admin.themes.*')" wire:navigate>
                        {{ __('Themes') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="chart-bar-square" :href="route('admin.analytics.index')" :current="request()->routeIs('admin.analytics.*')" wire:navigate>
                    {{ __('Analytics') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="cog-6-tooth" :href="route('admin.settings.index')" :current="request()->routeIs('admin.settings.*')" wire:navigate>
                    {{ __('Settings') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            @auth
                <flux:dropdown position="top" align="start" class="max-lg:hidden">
                    <flux:sidebar.profile :initials="auth()->user()->initials()" :name="auth()->user()->name" />
                    <flux:menu>
                        @if(auth()->user()->stores->count() > 1)
                            <flux:menu.radio.group>
                                @foreach(auth()->user()->stores as $storeOption)
                                    <flux:menu.radio
                                        wire:click="$dispatch('switch-store', { storeId: {{ $storeOption->id }} })"
                                        :checked="app()->bound('current_store') && app('current_store')->id === $storeOption->id"
                                    >
                                        {{ $storeOption->name }}
                                    </flux:menu.radio>
                                @endforeach
                            </flux:menu.radio.group>
                            <flux:menu.separator />
                        @endif
                        <flux:menu.item icon="arrow-right-start-on-rectangle" href="{{ route('admin.logout') }}">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endauth
        </flux:sidebar>

        <!-- Mobile Header -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            @auth
                <flux:dropdown position="top" align="end">
                    <flux:profile
                        :initials="auth()->user()->initials()"
                        icon-trailing="chevron-down"
                    />
                    <flux:menu>
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <flux:avatar :initials="auth()->user()->initials()" />
                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                        <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>
                        <flux:menu.separator />
                        <flux:menu.item icon="arrow-right-start-on-rectangle" href="{{ route('admin.logout') }}">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endauth
        </flux:header>

        <flux:main>
            @if(session()->has('toast'))
                <div
                    x-data="{ show: true }"
                    x-show="show"
                    x-init="setTimeout(() => show = false, 4000)"
                    x-transition
                    class="mb-4"
                >
                    <flux:callout variant="{{ session('toast.type', 'secondary') }}">
                        <flux:callout.heading>{{ session('toast.message') }}</flux:callout.heading>
                    </flux:callout>
                </div>
            @endif

            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>
