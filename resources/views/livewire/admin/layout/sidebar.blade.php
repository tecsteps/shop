<aside class="fixed inset-y-0 left-0 z-40 hidden w-64 border-r border-zinc-200 bg-white lg:block dark:border-zinc-800 dark:bg-zinc-900">
    <div class="flex h-full flex-col gap-5 p-4">
        <flux:brand name="Shop Admin" :href="route('admin.dashboard')" wire:navigate />
        <flux:separator />
        <nav class="flex flex-1 flex-col gap-1 overflow-y-auto" aria-label="Admin navigation">
            @php
                $items = [
                    ['admin.dashboard', 'chart-bar', 'Dashboard'], ['admin.products.index', 'cube', 'Products'], ['admin.collections.index', 'rectangle-stack', 'Collections'], ['admin.inventory.index', 'archive-box', 'Inventory'], ['admin.orders.index', 'shopping-bag', 'Orders'], ['admin.customers.index', 'users', 'Customers'], ['admin.discounts.index', 'tag', 'Discounts'], ['admin.pages.index', 'document-text', 'Pages'], ['admin.navigation.index', 'bars-3', 'Navigation'], ['admin.themes.index', 'paint-brush', 'Themes'], ['admin.analytics.index', 'chart-pie', 'Analytics'], ['admin.settings.index', 'cog-6-tooth', 'Settings'], ['admin.apps.index', 'squares-2x2', 'Apps'], ['admin.developers.index', 'code-bracket', 'Developers'],
                ];
            @endphp
            @foreach ($items as [$route, $icon, $label])
                @php
                    $isActive = request()->routeIs($route)
                        || ($route !== 'admin.dashboard' && request()->routeIs(Str::beforeLast($route, '.').'.*'));
                @endphp
                <a wire:key="admin-nav-{{ $route }}" href="{{ route($route) }}" wire:navigate @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition', 'bg-zinc-100 font-semibold dark:bg-zinc-800' => $isActive, 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! $isActive]) aria-current="{{ $isActive ? 'page' : 'false' }}">
                    <flux:icon :name="$icon" class="size-5" /> {{ $label }}
                </a>
            @endforeach
        </nav>
        <flux:separator />
        <livewire:admin.auth.logout />
    </div>
</aside>