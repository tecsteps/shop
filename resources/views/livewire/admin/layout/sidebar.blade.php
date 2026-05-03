<aside class="hidden border-r border-zinc-200 bg-white px-3 py-4 dark:border-zinc-800 dark:bg-zinc-900 lg:block">
    <div class="mb-6 flex items-center gap-3 px-3">
        <x-app-logo-icon class="size-8" />
        <div>
            <div class="text-sm font-semibold">Shop</div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Admin</div>
        </div>
    </div>

    <nav class="space-y-5">
        @foreach ($groups as $group)
            <div wire:key="admin-nav-group-{{ $loop->index }}" class="space-y-1">
                @if ($group['label'])
                    <div class="px-3 text-[0.7rem] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-500">
                        {{ $group['label'] }}
                    </div>
                @endif

                @foreach ($group['items'] as $item)
                    <a
                        wire:key="admin-nav-{{ $item['route'] }}"
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        @class([
                            'flex items-center gap-3 rounded-md px-3 py-2 text-sm transition',
                            'bg-zinc-950 font-semibold text-white dark:bg-white dark:text-zinc-950' => request()->routeIs($item['route']),
                            'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! request()->routeIs($item['route']),
                        ])
                    >
                        <flux:icon :name="$item['icon']" class="size-4" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>
</aside>
