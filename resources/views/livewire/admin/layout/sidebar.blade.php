<div>
    <div
        x-data="{ open: false }"
        x-on:toggle-admin-sidebar.window="open = !open"
        x-on:close-admin-sidebar.window="open = false"
    >
        <div
            x-show="open"
            x-transition.opacity
            x-on:click="open = false"
            class="fixed inset-0 z-30 bg-zinc-950/50 lg:hidden"
            style="display: none;"
            aria-hidden="true"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-zinc-200 bg-white transition-transform dark:border-zinc-800 dark:bg-zinc-900 lg:translate-x-0"
            :class="{ '-translate-x-full': !open, 'translate-x-0': open }"
            aria-label="Admin navigation"
        >
            <div class="flex h-16 items-center gap-2 border-b border-zinc-200 px-4 dark:border-zinc-800">
                <flux:brand name="{{ $currentStore?->name ?? config('app.name') }}" />
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Admin sections">
                @php
                    $linkClass = function (bool $active): string {
                        return $active
                            ? 'flex items-center gap-3 rounded-lg bg-zinc-900 px-3 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900'
                            : 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800';
                    };
                    $isActive = fn (string $prefix): bool => str_starts_with($currentRoute, $prefix);
                @endphp

                <a href="{{ route('admin.dashboard') }}" class="{{ $linkClass($currentRoute === 'admin.dashboard') }}" @if($currentRoute === 'admin.dashboard') aria-current="page" @endif wire:navigate>
                    <flux:icon.chart-bar class="size-5" variant="outline" />
                    <span>Dashboard</span>
                </a>

                <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Products</p>
                <div class="flex flex-col gap-1">
                    <a href="{{ route('admin.products.index') }}" class="{{ $linkClass($isActive('admin.products.')) }}" @if($isActive('admin.products.')) aria-current="page" @endif wire:navigate>
                        <flux:icon.cube class="size-5" variant="outline" />
                        <span>Products</span>
                    </a>
                    <a href="{{ route('admin.collections.index') }}" class="{{ $linkClass($isActive('admin.collections.')) }}" @if($isActive('admin.collections.')) aria-current="page" @endif wire:navigate>
                        <flux:icon.rectangle-stack class="size-5" variant="outline" />
                        <span>Collections</span>
                    </a>
                    <a href="{{ route('admin.inventory.index') }}" class="{{ $linkClass($isActive('admin.inventory.')) }}" @if($isActive('admin.inventory.')) aria-current="page" @endif wire:navigate>
                        <flux:icon.archive-box class="size-5" variant="outline" />
                        <span>Inventory</span>
                    </a>
                </div>

                <flux:separator class="my-3" />

                <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Orders</p>
                <div class="flex flex-col gap-1">
                    <a href="{{ route('admin.orders.index') }}" class="{{ $linkClass($isActive('admin.orders.')) }}" @if($isActive('admin.orders.')) aria-current="page" @endif wire:navigate>
                        <flux:icon.shopping-bag class="size-5" variant="outline" />
                        <span>Orders</span>
                    </a>
                </div>

                <flux:separator class="my-3" />

                <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Customers</p>
                <div class="flex flex-col gap-1">
                    <a href="{{ route('admin.customers.index') }}" class="{{ $linkClass($isActive('admin.customers.')) }}" @if($isActive('admin.customers.')) aria-current="page" @endif wire:navigate>
                        <flux:icon.users class="size-5" variant="outline" />
                        <span>Customers</span>
                    </a>
                </div>

                <flux:separator class="my-3" />

                <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Discounts</p>
                <div class="flex flex-col gap-1">
                    <a href="{{ route('admin.discounts.index') }}" class="{{ $linkClass($isActive('admin.discounts.')) }}" @if($isActive('admin.discounts.')) aria-current="page" @endif wire:navigate>
                        <flux:icon.tag class="size-5" variant="outline" />
                        <span>Discounts</span>
                    </a>
                </div>

                <flux:separator class="my-3" />

                <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Content</p>
                <div class="flex flex-col gap-1">
                    <a href="{{ route('admin.pages.index') }}" class="{{ $linkClass($isActive('admin.pages.')) }}" @if($isActive('admin.pages.')) aria-current="page" @endif wire:navigate>
                        <flux:icon.document-text class="size-5" variant="outline" />
                        <span>Pages</span>
                    </a>
                    <a href="{{ route('admin.navigation.index') }}" class="{{ $linkClass($isActive('admin.navigation.')) }}" @if($isActive('admin.navigation.')) aria-current="page" @endif wire:navigate>
                        <flux:icon.bars-3 class="size-5" variant="outline" />
                        <span>Navigation</span>
                    </a>
                    <a href="{{ route('admin.themes.index') }}" class="{{ $linkClass($isActive('admin.themes.')) }}" @if($isActive('admin.themes.')) aria-current="page" @endif wire:navigate>
                        <flux:icon.paint-brush class="size-5" variant="outline" />
                        <span>Themes</span>
                    </a>
                </div>

                <flux:separator class="my-3" />

                <div class="flex flex-col gap-1">
                    <a href="{{ route('admin.analytics.index') }}" class="{{ $linkClass($isActive('admin.analytics.')) }}" @if($isActive('admin.analytics.')) aria-current="page" @endif wire:navigate>
                        <flux:icon.chart-pie class="size-5" variant="outline" />
                        <span>Analytics</span>
                    </a>
                    <a href="{{ route('admin.settings.index') }}" class="{{ $linkClass($isActive('admin.settings.')) }}" @if($isActive('admin.settings.')) aria-current="page" @endif wire:navigate>
                        <flux:icon.cog-6-tooth class="size-5" variant="outline" />
                        <span>Settings</span>
                    </a>
                    <a href="{{ route('admin.apps.index') }}" class="{{ $linkClass($isActive('admin.apps.')) }}" @if($isActive('admin.apps.')) aria-current="page" @endif wire:navigate>
                        <flux:icon.squares-2x2 class="size-5" variant="outline" />
                        <span>Apps</span>
                    </a>
                    <a href="{{ route('admin.developers.index') }}" class="{{ $linkClass($isActive('admin.developers.')) }}" @if($isActive('admin.developers.')) aria-current="page" @endif wire:navigate>
                        <flux:icon.code-bracket class="size-5" variant="outline" />
                        <span>Developers</span>
                    </a>
                    <a href="{{ route('admin.search.settings') }}" class="{{ $linkClass($isActive('admin.search.')) }}" @if($isActive('admin.search.')) aria-current="page" @endif wire:navigate>
                        <flux:icon.magnifying-glass class="size-5" variant="outline" />
                        <span>Search</span>
                    </a>
                </div>
            </nav>
        </aside>
    </div>
</div>
