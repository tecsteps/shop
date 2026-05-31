<div>
<flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.header>
        <flux:sidebar.brand
            :href="route('admin.dashboard')"
            :name="$currentStore->name ?? config('app.name')"
            wire:navigate
        >
            <x-slot name="logo">
                <div class="flex size-8 items-center justify-center rounded-md bg-zinc-900 text-white dark:bg-white dark:text-zinc-900">
                    <flux:icon.building-storefront class="size-5" />
                </div>
            </x-slot>
        </flux:sidebar.brand>
        <flux:sidebar.collapse class="lg:hidden" />
    </flux:sidebar.header>

    <flux:sidebar.nav>
        <flux:sidebar.item
            icon="chart-bar"
            :href="route('admin.dashboard')"
            :current="request()->routeIs('admin.dashboard')"
            wire:navigate
        >
            {{ __('Dashboard') }}
        </flux:sidebar.item>

        <flux:sidebar.group :heading="__('Products')" class="grid">
            <flux:sidebar.item icon="cube" :href="route('admin.products.index')" :current="request()->routeIs('admin.products.*')" wire:navigate>
                {{ __('Products') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="rectangle-stack" :href="route('admin.collections.index')" :current="request()->routeIs('admin.collections.*')" wire:navigate>
                {{ __('Collections') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="archive-box" :href="route('admin.inventory.index')" :current="request()->routeIs('admin.inventory.*')" wire:navigate>
                {{ __('Inventory') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.group :heading="__('Orders')" class="grid">
            <flux:sidebar.item icon="shopping-bag" :href="route('admin.orders.index')" :current="request()->routeIs('admin.orders.*')" wire:navigate>
                {{ __('Orders') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.group :heading="__('Customers')" class="grid">
            <flux:sidebar.item icon="users" :href="route('admin.customers.index')" :current="request()->routeIs('admin.customers.*')" wire:navigate>
                {{ __('Customers') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.group :heading="__('Discounts')" class="grid">
            <flux:sidebar.item icon="tag" :href="route('admin.discounts.index')" :current="request()->routeIs('admin.discounts.*')" wire:navigate>
                {{ __('Discounts') }}
            </flux:sidebar.item>
        </flux:sidebar.group>

        <flux:sidebar.group :heading="__('Content')" class="grid">
            <flux:sidebar.item icon="document-text" :href="route('admin.pages.index')" :current="request()->routeIs('admin.pages.*')" wire:navigate>
                {{ __('Pages') }}
            </flux:sidebar.item>
            <flux:sidebar.item icon="bars-3" :href="route('admin.navigation.index')" :current="request()->routeIs('admin.navigation.*')" wire:navigate>
                {{ __('Navigation') }}
            </flux:sidebar.item>
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
        <flux:sidebar.item icon="squares-2x2" :href="route('admin.apps.index')" :current="request()->routeIs('admin.apps.*')" wire:navigate>
            {{ __('Apps') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="code-bracket" :href="route('admin.developers.index')" :current="request()->routeIs('admin.developers.*')" wire:navigate>
            {{ __('Developers') }}
        </flux:sidebar.item>
    </flux:sidebar.nav>
</flux:sidebar>
</div>
