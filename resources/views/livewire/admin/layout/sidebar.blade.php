<div class="flex h-full flex-col">
    {{-- Brand --}}
    <div class="flex h-16 items-center gap-2 px-4 border-b border-zinc-200 dark:border-zinc-700">
        <flux:brand name="Shop Admin" class="max-lg:hidden" />
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        {{-- Dashboard --}}
        <a href="{{ route('admin.dashboard') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
            <flux:icon name="chart-bar" variant="outline" class="size-5" />
            Dashboard
        </a>

        <flux:separator />

        {{-- Products group --}}
        <p class="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Products</p>

        <a href="{{ route('admin.products.index') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.products.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
            <flux:icon name="cube" variant="outline" class="size-5" />
            Products
        </a>

        <a href="{{ route('admin.collections.index') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.collections.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
            <flux:icon name="rectangle-stack" variant="outline" class="size-5" />
            Collections
        </a>

        <a href="{{ route('admin.inventory.index') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.inventory.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
            <flux:icon name="archive-box" variant="outline" class="size-5" />
            Inventory
        </a>

        <flux:separator />

        {{-- Orders group --}}
        <p class="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Orders</p>

        <a href="{{ route('admin.orders.index') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.orders.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
            <flux:icon name="shopping-bag" variant="outline" class="size-5" />
            Orders
        </a>

        <flux:separator />

        {{-- Customers group --}}
        <p class="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Customers</p>

        <a href="{{ route('admin.customers.index') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.customers.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
            <flux:icon name="users" variant="outline" class="size-5" />
            Customers
        </a>

        <flux:separator />

        {{-- Discounts group --}}
        <p class="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Discounts</p>

        <a href="{{ route('admin.discounts.index') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.discounts.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
            <flux:icon name="tag" variant="outline" class="size-5" />
            Discounts
        </a>

        <flux:separator />

        {{-- Content group --}}
        <p class="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Content</p>

        <a href="{{ route('admin.pages.index') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.pages.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
            <flux:icon name="document-text" variant="outline" class="size-5" />
            Pages
        </a>

        <a href="{{ route('admin.navigation.index') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.navigation.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
            <flux:icon name="bars-3" variant="outline" class="size-5" />
            Navigation
        </a>

        <a href="{{ route('admin.themes.index') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.themes.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
            <flux:icon name="paint-brush" variant="outline" class="size-5" />
            Themes
        </a>

        <flux:separator />

        {{-- Bottom items --}}
        <a href="{{ route('admin.analytics.index') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.analytics.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
            <flux:icon name="chart-pie" variant="outline" class="size-5" />
            Analytics
        </a>

        <a href="{{ route('admin.settings.index') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.settings.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
            <flux:icon name="cog-6-tooth" variant="outline" class="size-5" />
            Settings
        </a>

        <a href="{{ route('admin.apps.index') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.apps.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
            <flux:icon name="squares-2x2" variant="outline" class="size-5" />
            Apps
        </a>

        <a href="{{ route('admin.developers.index') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.developers.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white' }}">
            <flux:icon name="code-bracket" variant="outline" class="size-5" />
            Developers
        </a>
    </nav>
</div>
