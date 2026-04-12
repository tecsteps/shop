<div class="space-y-6 p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Navigation</flux:heading>
    </div>

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">Create menu</flux:heading>
        <form wire:submit="createMenu" class="mt-4 flex items-end gap-3">
            <flux:field class="flex-1">
                <flux:label>Title</flux:label>
                <flux:input wire:model="newMenuTitle" placeholder="Main menu" />
                <flux:error name="newMenuTitle" />
            </flux:field>
            <flux:button type="submit" variant="primary" icon="plus">Add menu</flux:button>
        </form>
    </div>

    <div class="space-y-4">
        @forelse ($menus as $menu)
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="lg">{{ $menu->title }}</flux:heading>
                        <p class="text-xs text-zinc-500">{{ $menu->handle }}</p>
                    </div>
                    <div class="flex gap-2">
                        <flux:button size="sm" icon="plus" wire:click="openItemModal({{ $menu->id }})">Add item</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="deleteMenu({{ $menu->id }})" wire:confirm="Delete this menu?">Delete</flux:button>
                    </div>
                </div>

                <div class="mt-4">
                    @if ($menu->items->isEmpty())
                        <p class="text-sm text-zinc-500">No items yet.</p>
                    @else
                        <ul class="divide-y divide-zinc-200 rounded border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                            @foreach ($menu->items as $item)
                                <li class="flex items-center justify-between p-3 text-sm">
                                    <div>
                                        <span class="font-medium">{{ $item->label }}</span>
                                        <span class="ml-2 text-xs text-zinc-500">({{ $item->type->value }})</span>
                                    </div>
                                    <div class="flex gap-1">
                                        <flux:button size="xs" variant="ghost" icon="arrow-up" wire:click="moveItem({{ $item->id }}, 'up')" />
                                        <flux:button size="xs" variant="ghost" icon="arrow-down" wire:click="moveItem({{ $item->id }}, 'down')" />
                                        <flux:button size="xs" variant="danger" wire:click="deleteItem({{ $item->id }})">Remove</flux:button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
                No menus yet. Create one above.
            </div>
        @endforelse
    </div>

    <flux:modal wire:model.self="showItemModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Add menu item</flux:heading>
            <flux:field>
                <flux:label>Type</flux:label>
                <flux:select wire:model.live="newItemType">
                    <flux:select.option value="link">Link</flux:select.option>
                    <flux:select.option value="page">Page</flux:select.option>
                    <flux:select.option value="collection">Collection</flux:select.option>
                    <flux:select.option value="product">Product</flux:select.option>
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>Label</flux:label>
                <flux:input wire:model="newItemLabel" />
                <flux:error name="newItemLabel" />
            </flux:field>
            @if ($newItemType === 'link')
                <flux:field>
                    <flux:label>URL</flux:label>
                    <flux:input wire:model="newItemUrl" placeholder="/collections/featured" />
                </flux:field>
            @else
                <flux:field>
                    <flux:label>Resource ID</flux:label>
                    <flux:input type="number" wire:model="newItemResourceId" />
                </flux:field>
            @endif
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeItemModal">Cancel</flux:button>
                <flux:button variant="primary" wire:click="addItem">Add</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
