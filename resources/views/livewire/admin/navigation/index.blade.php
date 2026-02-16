<div>
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Navigation</h2>
        <flux:button wire:click="openMenuModal" variant="primary">Add menu</flux:button>
    </div>

    <div class="space-y-4">
        @foreach($menus as $menu)
            <x-admin.card>
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $menu->name }}</h3>
                    <div class="flex gap-2">
                        <flux:button wire:click="openMenuModal({{ $menu->id }})" variant="ghost" size="sm">Edit</flux:button>
                        <flux:button wire:click="deleteMenu({{ $menu->id }})" variant="danger" size="sm">Delete</flux:button>
                    </div>
                </div>
                <ul class="space-y-1">
                    @foreach($menu->items as $item)
                        <li class="flex items-center justify-between rounded border border-gray-100 px-3 py-2 text-sm dark:border-zinc-700">
                            <span class="text-gray-900 dark:text-white">{{ $item->title }} <span class="text-gray-400">{{ $item->url }}</span></span>
                            <flux:button wire:click="deleteItem({{ $item->id }})" variant="ghost" size="sm" icon="x-mark" />
                        </li>
                    @endforeach
                </ul>
                <flux:button wire:click="openItemModal({{ $menu->id }})" variant="ghost" size="sm" class="mt-2">Add item</flux:button>
            </x-admin.card>
        @endforeach
    </div>

    <flux:modal wire:model="showMenuModal">
        <div class="space-y-4 p-6">
            <h3 class="text-lg font-semibold">{{ $editingMenuId ? 'Edit' : 'Add' }} Menu</h3>
            <flux:input wire:model="menuName" label="Menu name" />
            <flux:input wire:model="menuHandle" label="Handle" />
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showMenuModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="saveMenu" variant="primary">Save</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showItemModal">
        <div class="space-y-4 p-6">
            <h3 class="text-lg font-semibold">Add Item</h3>
            <flux:input wire:model="itemTitle" label="Title" />
            <flux:input wire:model="itemUrl" label="URL" />
            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showItemModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="saveItem" variant="primary">Add</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
