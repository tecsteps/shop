<div class="space-y-4">
    <flux:heading size="xl">Navigation</flux:heading>

    <form wire:submit="addMenu" class="flex items-end gap-3 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
        <flux:input wire:model="newMenuHandle" label="Handle" placeholder="main-menu" />
        <flux:input wire:model="newMenuTitle" label="Title" placeholder="Main menu" />
        <flux:button type="submit" variant="primary">Add menu</flux:button>
    </form>

    @foreach ($menus as $menu)
        <div wire:key="menu-{{ $menu->id }}" class="space-y-3 rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">{{ $menu->title }} <span class="text-xs text-zinc-500">({{ $menu->handle }})</span></flux:heading>
                <flux:button size="xs" variant="danger" wire:click="removeMenu({{ $menu->id }})" wire:confirm="Delete menu?">Remove menu</flux:button>
            </div>
            <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($menu->items as $item)
                    <li wire:key="item-{{ $item->id }}" class="flex items-center justify-between py-2 text-sm">
                        <span>{{ $item->label }} &mdash; {{ $item->url }}</span>
                        <div class="flex gap-1">
                            <flux:button size="xs" wire:click="moveItem({{ $item->id }}, -1)">Up</flux:button>
                            <flux:button size="xs" wire:click="moveItem({{ $item->id }}, 1)">Down</flux:button>
                            <flux:button size="xs" variant="danger" wire:click="removeItem({{ $item->id }})">Remove</flux:button>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="grid gap-2 sm:grid-cols-3">
                <flux:input wire:model="newItem.{{ $menu->id }}.label" placeholder="Label" />
                <flux:input wire:model="newItem.{{ $menu->id }}.url" placeholder="/path" />
                <flux:button wire:click="addItem({{ $menu->id }})">Add item</flux:button>
            </div>
        </div>
    @endforeach
</div>
