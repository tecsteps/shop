<nav class="flex h-full flex-col" aria-label="Admin navigation">
    <div class="flex h-16 items-center gap-3 border-b border-zinc-200 px-5 dark:border-zinc-800"><span class="flex size-9 items-center justify-center rounded-lg bg-zinc-950 text-white dark:bg-white dark:text-zinc-950"><flux:icon.shopping-bag class="size-5" /></span><div><div class="font-semibold">Shop</div><div class="text-xs text-zinc-500">Commerce platform</div></div></div>
    <div class="flex-1 overflow-y-auto p-3">
        @php
            $groups = [
                '' => [['Dashboard', '/admin', 'chart-bar']],
                'Products' => [['Products', '/admin/products', 'cube'], ['Collections', '/admin/collections', 'rectangle-stack'], ['Inventory', '/admin/inventory', 'archive-box']],
                'Orders' => [['Orders', '/admin/orders', 'shopping-bag']],
                'Customers' => [['Customers', '/admin/customers', 'users']],
                'Discounts' => [['Discounts', '/admin/discounts', 'tag']],
                'Content' => [['Pages', '/admin/pages', 'document-text'], ['Navigation', '/admin/navigation', 'bars-3'], ['Themes', '/admin/themes', 'paint-brush']],
                'Insights' => [['Analytics', '/admin/analytics', 'chart-pie'], ['Search', '/admin/settings/search', 'magnifying-glass']],
                'Platform' => [['Settings', '/admin/settings', 'cog-6-tooth'], ['Apps', '/admin/apps', 'squares-2x2'], ['Developers', '/admin/developers', 'code-bracket']],
            ];
        @endphp
        @foreach ($groups as $group => $items)
            @if ($group)<div class="px-3 pb-2 pt-5 text-[0.7rem] font-bold uppercase tracking-widest text-zinc-500">{{ $group }}</div>@endif
            <div class="grid gap-1">@foreach ($items as [$label, $href, $icon])<a href="{{ $href }}" wire:navigate @click="menuOpen = false" wire:key="admin-nav-{{ $label }}" @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-zinc-100 dark:hover:bg-zinc-800', 'bg-zinc-100 font-semibold dark:bg-zinc-800' => request()->path() === ltrim($href, '/') || ($href !== '/admin' && request()->is(ltrim($href, '/').'*'))])><flux:icon :name="$icon" class="size-5 text-zinc-500" /><span>{{ $label }}</span></a>@endforeach</div>
        @endforeach
    </div>
</nav>
