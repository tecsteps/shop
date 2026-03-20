<div>
    <flux:heading size="xl">Navigation</flux:heading>
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        @forelse($menus as $menu)
            <div wire:key="menu-{{ $menu->id }}" class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:heading size="lg">{{ $menu->title }}</flux:heading>
                <p class="text-sm text-gray-500">Handle: {{ $menu->handle }}</p>
                @if($menu->items->isNotEmpty())
                    <ul class="mt-4 space-y-1">
                        @foreach($menu->items->sortBy('position') as $item)
                            <li class="flex items-center justify-between rounded px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                <span>{{ $item->label }}</span>
                                <span class="text-xs text-gray-400">{{ $item->link_type }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm text-gray-500">No items.</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500">No navigation menus.</p>
        @endforelse
    </div>
</div>
