@php
    $navGroups = [
        'Products' => [
            ['label' => 'Products', 'route' => 'admin.products.index', 'pattern' => 'admin.products.*', 'icon' => 'cube'],
            ['label' => 'Collections', 'route' => 'admin.collections.index', 'pattern' => 'admin.collections.*', 'icon' => 'rectangle-stack'],
            ['label' => 'Inventory', 'route' => 'admin.inventory.index', 'pattern' => 'admin.inventory.*', 'icon' => 'archive-box'],
        ],
        'Orders' => [
            ['label' => 'Orders', 'route' => 'admin.orders.index', 'pattern' => 'admin.orders.*', 'icon' => 'shopping-bag'],
        ],
        'Customers' => [
            ['label' => 'Customers', 'route' => 'admin.customers.index', 'pattern' => 'admin.customers.*', 'icon' => 'users'],
        ],
        'Discounts' => [
            ['label' => 'Discounts', 'route' => 'admin.discounts.index', 'pattern' => 'admin.discounts.*', 'icon' => 'tag'],
        ],
        'Content' => [
            ['label' => 'Pages', 'route' => 'admin.pages.index', 'pattern' => 'admin.pages.*', 'icon' => 'document-text'],
            ['label' => 'Navigation', 'route' => 'admin.navigation.index', 'pattern' => 'admin.navigation.*', 'icon' => 'bars-3'],
            ['label' => 'Themes', 'route' => 'admin.themes.index', 'pattern' => 'admin.themes.*', 'icon' => 'paint-brush'],
        ],
    ];

    $utilityLinks = [
        ['label' => 'Analytics', 'route' => 'admin.analytics.index', 'pattern' => 'admin.analytics.*', 'icon' => 'chart-pie'],
        ['label' => 'Settings', 'route' => 'admin.settings.index', 'pattern' => 'admin.settings.*', 'icon' => 'cog-6-tooth'],
        ['label' => 'Apps', 'route' => 'admin.apps.index', 'pattern' => 'admin.apps.*', 'icon' => 'squares-2x2'],
        ['label' => 'Developers', 'route' => 'admin.developers.index', 'pattern' => 'admin.developers.*', 'icon' => 'code-bracket'],
        ['label' => 'Search', 'route' => 'admin.search.settings', 'pattern' => 'admin.search.*', 'icon' => 'magnifying-glass'],
    ];
@endphp

<div
    class="flex h-full flex-col border-e border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
    :class="$wire.collapsed ? 'lg:w-16' : 'lg:w-64'"
>
    {{-- Brand --}}
    <div class="flex h-16 items-center gap-3 border-b border-zinc-200 px-4 dark:border-zinc-700">
        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900">
            <flux:icon.building-storefront class="size-4" />
        </div>
        <div x-show="! $wire.collapsed" x-cloak class="min-w-0">
            <div class="truncate text-sm font-semibold">{{ config('app.name') }}</div>
            <div class="truncate text-xs text-zinc-400 dark:text-zinc-500">{{ app('current_store')->name }}</div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 space-y-4 overflow-y-auto px-3 py-4" aria-label="Admin navigation">
        <a
            href="{{ route('admin.dashboard') }}"
            wire:navigate
            @class([
                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => request()->routeIs('admin.dashboard'),
                'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! request()->routeIs('admin.dashboard'),
            ])
            aria-current="{{ request()->routeIs('admin.dashboard') ? 'page' : 'false' }}"
            @click="sidebarOpen = false"
        >
            <flux:icon.chart-bar class="size-5 shrink-0" />
            <span x-show="! $wire.collapsed" x-cloak>Dashboard</span>
        </a>

        @foreach ($navGroups as $group => $items)
            <div>
                <p x-show="! $wire.collapsed" x-cloak class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                    {{ $group }}
                </p>
                <div class="space-y-1">
                    @foreach ($items as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            @class([
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => request()->routeIs($item['pattern']),
                                'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! request()->routeIs($item['pattern']),
                            ])
                            aria-current="{{ request()->routeIs($item['pattern']) ? 'page' : 'false' }}"
                            @click="sidebarOpen = false"
                        >
                            <flux:icon :name="$item['icon']" class="size-5 shrink-0" />
                            <span x-show="! $wire.collapsed" x-cloak>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <flux:separator x-show="! $wire.collapsed" x-cloak />
        @endforeach

        <div class="space-y-1">
            @foreach ($utilityLinks as $item)
                <a
                    href="{{ route($item['route']) }}"
                    wire:navigate
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                        'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => request()->routeIs($item['pattern']),
                        'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! request()->routeIs($item['pattern']),
                    ])
                    aria-current="{{ request()->routeIs($item['pattern']) ? 'page' : 'false' }}"
                    @click="sidebarOpen = false"
                >
                    <flux:icon :name="$item['icon']" class="size-5 shrink-0" />
                    <span x-show="! $wire.collapsed" x-cloak>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    {{-- Footer: collapse toggle --}}
    <div class="hidden border-t border-zinc-200 p-3 lg:block dark:border-zinc-700">
        <flux:button
            variant="subtle"
            class="w-full justify-start"
            :icon="$collapsed ? 'chevron-double-right' : 'chevron-double-left'"
            wire:click="toggle"
        >
            <span x-show="! $wire.collapsed" x-cloak>Collapse</span>
        </flux:button>
    </div>
</div>
