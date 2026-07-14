@php
    $items = [
        ['label' => 'Dashboard', 'path' => '/admin', 'match' => 'admin.dashboard', 'icon' => 'chart-bar-square'],
        ['group' => 'Products'],
        ['label' => 'Products', 'path' => '/admin/products', 'match' => 'admin.products.*', 'icon' => 'cube'],
        ['label' => 'Collections', 'path' => '/admin/collections', 'match' => 'admin.collections.*', 'icon' => 'rectangle-stack'],
        ['label' => 'Inventory', 'path' => '/admin/inventory', 'match' => 'admin.inventory.*', 'icon' => 'archive-box'],
        ['group' => 'Sales'],
        ['label' => 'Orders', 'path' => '/admin/orders', 'match' => 'admin.orders.*', 'icon' => 'shopping-bag'],
        ['label' => 'Customers', 'path' => '/admin/customers', 'match' => 'admin.customers.*', 'icon' => 'users'],
        ['label' => 'Discounts', 'path' => '/admin/discounts', 'match' => 'admin.discounts.*', 'icon' => 'tag'],
        ['group' => 'Content'],
        ['label' => 'Pages', 'path' => '/admin/pages', 'match' => 'admin.pages.*', 'icon' => 'document-text'],
        ['label' => 'Navigation', 'path' => '/admin/navigation', 'match' => 'admin.navigation.*', 'icon' => 'bars-3', 'roles' => ['owner','admin']],
        ['label' => 'Themes', 'path' => '/admin/themes', 'match' => 'admin.themes.*', 'icon' => 'paint-brush', 'roles' => ['owner','admin']],
        ['group' => 'Insights'],
        ['label' => 'Analytics', 'path' => '/admin/analytics', 'match' => 'admin.analytics.*', 'icon' => 'chart-pie', 'roles' => ['owner','admin','staff']],
        ['label' => 'Search', 'path' => '/admin/search/settings', 'match' => 'admin.search.*', 'icon' => 'magnifying-glass', 'roles' => ['owner','admin']],
        ['label' => 'Settings', 'path' => '/admin/settings', 'match' => 'admin.settings.*', 'icon' => 'cog-6-tooth', 'roles' => ['owner','admin']],
        ['label' => 'Apps', 'path' => '/admin/apps', 'match' => 'admin.apps.*', 'icon' => 'squares-2x2', 'roles' => ['owner','admin']],
        ['label' => 'Developers', 'path' => '/admin/developers', 'match' => 'admin.developers.*', 'icon' => 'code-bracket', 'roles' => ['owner','admin']],
    ];
@endphp
<aside class="flex h-full flex-col border-r border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900" aria-label="Admin sidebar">
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-5 dark:border-slate-800">
        <span class="grid size-9 place-items-center rounded-xl bg-blue-700 font-semibold text-white">S</span>
        <div class="min-w-0"><p class="truncate font-semibold">{{ config('app.name', 'Shop') }}</p><p class="truncate text-xs text-slate-500">Commerce admin</p></div>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        @foreach($items as $item)
            @if(isset($item['group']))
                <p class="mb-1 mt-5 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400 first:mt-0">{{ $item['group'] }}</p>
            @elseif(!isset($item['roles']) || in_array($role, $item['roles'], true))
                @php $active = request()->routeIs($item['match']) || ($item['path'] === '/admin' && request()->path() === 'admin'); @endphp
                <a href="{{ url($item['path']) }}" wire:navigate class="mb-1 flex min-h-10 items-center gap-3 rounded-lg px-3 text-sm {{ $active ? 'bg-blue-50 font-semibold text-blue-800 dark:bg-blue-950/60 dark:text-blue-200' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}" @if($active) aria-current="page" @endif>
                    <flux:icon :name="$item['icon']" class="size-5 shrink-0" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>
    <div class="border-t border-slate-200 p-4 text-xs text-slate-500 dark:border-slate-800">Signed in as {{ ucfirst($role) }}</div>
</aside>
