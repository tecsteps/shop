<div>
    {{-- Mobile backdrop --}}
    @if (! $collapsed)
        <div
            wire:click="toggle"
            class="fixed inset-0 z-30 bg-black/50 lg:hidden"
        ></div>
    @endif

    {{-- Sidebar --}}
    <aside
        class="fixed left-0 top-0 z-40 flex h-full w-64 flex-col border-r border-gray-200 bg-white transition-transform dark:border-gray-800 dark:bg-gray-900 {{ $collapsed ? '-translate-x-full lg:translate-x-0' : 'translate-x-0' }}"
    >
        {{-- Brand --}}
        <div class="flex h-16 items-center gap-3 px-6">
            <flux:brand name="Shop Admin" />
        </div>

        <flux:separator />

        {{-- Navigation --}}
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            {{-- Dashboard --}}
            <a
                href="{{ route('admin.dashboard') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.dashboard'),
                    'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! str_starts_with($currentRoute, 'admin.dashboard'),
                ])
            >
                <flux:icon name="chart-bar" variant="outline" class="h-5 w-5" />
                Dashboard
            </a>

            <flux:separator />

            {{-- Products Group --}}
            <p class="px-3 pt-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Products</p>
            <a
                href="{{ route('admin.products.index') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.products'),
                    'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! str_starts_with($currentRoute, 'admin.products'),
                ])
            >
                <flux:icon name="cube" variant="outline" class="h-5 w-5" />
                Products
            </a>
            <a
                href="{{ route('admin.collections.index') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.collections'),
                    'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! str_starts_with($currentRoute, 'admin.collections'),
                ])
            >
                <flux:icon name="rectangle-stack" variant="outline" class="h-5 w-5" />
                Collections
            </a>
            <a
                href="{{ route('admin.inventory.index') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.inventory'),
                    'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! str_starts_with($currentRoute, 'admin.inventory'),
                ])
            >
                <flux:icon name="archive-box" variant="outline" class="h-5 w-5" />
                Inventory
            </a>

            <flux:separator />

            {{-- Orders Group --}}
            <p class="px-3 pt-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Orders</p>
            <a
                href="{{ route('admin.orders.index') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.orders'),
                    'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! str_starts_with($currentRoute, 'admin.orders'),
                ])
            >
                <flux:icon name="shopping-bag" variant="outline" class="h-5 w-5" />
                Orders
            </a>

            <flux:separator />

            {{-- Customers Group --}}
            <p class="px-3 pt-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Customers</p>
            <a
                href="{{ route('admin.customers.index') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.customers'),
                    'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! str_starts_with($currentRoute, 'admin.customers'),
                ])
            >
                <flux:icon name="users" variant="outline" class="h-5 w-5" />
                Customers
            </a>

            <flux:separator />

            {{-- Discounts Group --}}
            <p class="px-3 pt-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Discounts</p>
            <a
                href="{{ route('admin.discounts.index') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.discounts'),
                    'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! str_starts_with($currentRoute, 'admin.discounts'),
                ])
            >
                <flux:icon name="tag" variant="outline" class="h-5 w-5" />
                Discounts
            </a>

            <flux:separator />

            {{-- Content Group --}}
            <p class="px-3 pt-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Content</p>
            <a
                href="{{ route('admin.pages.index') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.pages'),
                    'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! str_starts_with($currentRoute, 'admin.pages'),
                ])
            >
                <flux:icon name="document-text" variant="outline" class="h-5 w-5" />
                Pages
            </a>
            <a
                href="{{ route('admin.navigation.index') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.navigation'),
                    'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! str_starts_with($currentRoute, 'admin.navigation'),
                ])
            >
                <flux:icon name="bars-3" variant="outline" class="h-5 w-5" />
                Navigation
            </a>
            <a
                href="{{ route('admin.themes.index') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.themes'),
                    'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! str_starts_with($currentRoute, 'admin.themes'),
                ])
            >
                <flux:icon name="paint-brush" variant="outline" class="h-5 w-5" />
                Themes
            </a>

            <flux:separator />

            {{-- Bottom Group --}}
            <a
                href="{{ route('admin.analytics.index') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.analytics'),
                    'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! str_starts_with($currentRoute, 'admin.analytics'),
                ])
            >
                <flux:icon name="chart-pie" variant="outline" class="h-5 w-5" />
                Analytics
            </a>
            <a
                href="{{ route('admin.settings.index') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.settings'),
                    'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! str_starts_with($currentRoute, 'admin.settings'),
                ])
            >
                <flux:icon name="cog-6-tooth" variant="outline" class="h-5 w-5" />
                Settings
            </a>
            <a
                href="{{ route('admin.apps.index') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.apps'),
                    'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! str_starts_with($currentRoute, 'admin.apps'),
                ])
            >
                <flux:icon name="squares-2x2" variant="outline" class="h-5 w-5" />
                Apps
            </a>
            <a
                href="{{ route('admin.developers.index') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' => str_starts_with($currentRoute, 'admin.developers'),
                    'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => ! str_starts_with($currentRoute, 'admin.developers'),
                ])
            >
                <flux:icon name="code-bracket" variant="outline" class="h-5 w-5" />
                Developers
            </a>
        </nav>
    </aside>
</div>
