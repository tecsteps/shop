<div class="flex h-full flex-col overflow-y-auto">
    {{-- Brand --}}
    <div class="flex h-16 shrink-0 items-center gap-2 px-4">
        <flux:icon name="shopping-bag" class="text-zinc-900 dark:text-white" />
        <span class="text-lg font-semibold text-zinc-900 dark:text-white">{{ config('app.name') }}</span>
    </div>

    <flux:separator />

    {{-- Navigation --}}
    <nav class="flex-1 space-y-1 px-3 py-4">
        {{-- Dashboard --}}
        <x-admin.nav-link route="admin.dashboard" icon="chart-bar" label="Dashboard" />

        <flux:separator class="my-3" />

        {{-- Products group --}}
        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Products</p>
        <x-admin.nav-link route="admin.products.index" icon="cube" label="Products" />
        <x-admin.nav-link route="admin.collections.index" icon="rectangle-stack" label="Collections" />
        <x-admin.nav-link route="admin.inventory.index" icon="archive-box" label="Inventory" />

        <flux:separator class="my-3" />

        {{-- Orders group --}}
        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Orders</p>
        <x-admin.nav-link route="admin.orders.index" icon="shopping-bag" label="Orders" />

        <flux:separator class="my-3" />

        {{-- Customers group --}}
        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Customers</p>
        <x-admin.nav-link route="admin.customers.index" icon="users" label="Customers" />

        <flux:separator class="my-3" />

        {{-- Discounts group --}}
        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Discounts</p>
        <x-admin.nav-link route="admin.discounts.index" icon="tag" label="Discounts" />

        <flux:separator class="my-3" />

        {{-- Content group --}}
        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Content</p>
        <x-admin.nav-link route="admin.pages.index" icon="document-text" label="Pages" />
        <x-admin.nav-link route="admin.navigation.index" icon="bars-3" label="Navigation" />

        <flux:separator class="my-3" />

        {{-- Bottom links --}}
        <x-admin.nav-link route="admin.analytics.index" icon="chart-pie" label="Analytics" />
        <x-admin.nav-link route="admin.settings.index" icon="cog-6-tooth" label="Settings" />
        <x-admin.nav-link route="admin.developers.index" icon="code-bracket" label="Developers" />
    </nav>
</div>
