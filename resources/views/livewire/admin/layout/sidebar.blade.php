@php
    $navItems = [
        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'chart-bar'],
        ['separator' => true, 'group' => 'PRODUCTS'],
        ['route' => 'admin.products.index', 'label' => 'Products', 'icon' => 'cube'],
        ['route' => 'admin.collections.index', 'label' => 'Collections', 'icon' => 'rectangle-stack'],
        ['route' => 'admin.inventory.index', 'label' => 'Inventory', 'icon' => 'archive-box'],
        ['separator' => true, 'group' => 'ORDERS'],
        ['route' => 'admin.orders.index', 'label' => 'Orders', 'icon' => 'shopping-bag'],
        ['separator' => true, 'group' => 'CUSTOMERS'],
        ['route' => 'admin.customers.index', 'label' => 'Customers', 'icon' => 'users'],
        ['separator' => true, 'group' => 'DISCOUNTS'],
        ['route' => 'admin.discounts.index', 'label' => 'Discounts', 'icon' => 'tag'],
    ];
@endphp

<div class="flex flex-col h-full">
    {{-- Brand --}}
    <div class="flex items-center gap-2 px-4 h-16 border-b border-zinc-200 dark:border-zinc-700 shrink-0">
        <flux:brand name="Admin" class="text-zinc-900 dark:text-white" />
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        @foreach ($navItems as $item)
            @if (isset($item['separator']))
                <div class="pt-4 pb-1">
                    <flux:separator />
                    @if (isset($item['group']))
                        <p class="px-3 pt-3 text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                            {{ $item['group'] }}
                        </p>
                    @endif
                </div>
            @else
                @php
                    $isActive = str_starts_with($currentRoute, $item['route']);
                    $routeExists = \Illuminate\Support\Facades\Route::has($item['route']);
                @endphp
                @if ($routeExists)
                    <a
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        @click="sidebarOpen = false"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $isActive ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-white font-bold' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 hover:text-zinc-900 dark:hover:text-white' }}"
                    >
                        <flux:icon :name="$item['icon']" class="size-5 shrink-0" />
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-zinc-400 dark:text-zinc-600 cursor-not-allowed">
                        <flux:icon :name="$item['icon']" class="size-5 shrink-0" />
                        {{ $item['label'] }}
                    </span>
                @endif
            @endif
        @endforeach
    </nav>
</div>
