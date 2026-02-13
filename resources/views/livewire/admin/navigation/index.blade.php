<div class="space-y-6">
    <flux:heading size="xl">Navigation</flux:heading>

    @forelse ($menus as $menu)
        <div wire:key="menu-{{ $menu->id }}" class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-3">{{ $menu->title }}</flux:heading>
            <flux:text class="mb-3 text-sm">Handle: {{ $menu->handle }}</flux:text>

            @if ($menu->items->isNotEmpty())
                <div class="space-y-2">
                    @foreach ($menu->items->sortBy('position') as $item)
                        <div wire:key="item-{{ $item->id }}" class="flex items-center justify-between rounded bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-900">
                            <span>{{ $item->label }}</span>
                            <span class="text-zinc-500 dark:text-zinc-400">{{ $item->url }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <flux:text class="text-sm">No items in this menu.</flux:text>
            @endif
        </div>
    @empty
        <div class="rounded-lg border border-zinc-200 p-8 text-center dark:border-zinc-700">
            <flux:text>No navigation menus found.</flux:text>
        </div>
    @endforelse
</div>
