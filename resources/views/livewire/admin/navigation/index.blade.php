<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Navigation</flux:heading>
        <flux:button variant="primary" icon="plus" wire:click="openMenuModal()">Add menu</flux:button>
    </div>

    @foreach ($menus as $menu)
        <div class="mb-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <flux:heading size="md">{{ $menu->title }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500">Handle: {{ $menu->handle }}</flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:button size="sm" variant="ghost" wire:click="openMenuModal({{ $menu->id }})">Edit</flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="deleteMenu({{ $menu->id }})" wire:confirm="Delete this menu and all its items?">Delete</flux:button>
                </div>
            </div>

            @if ($menu->items->isNotEmpty())
                <div class="mb-3 space-y-1">
                    @foreach ($menu->items as $item)
                        <div class="flex items-center justify-between rounded border border-zinc-100 px-3 py-2 dark:border-zinc-700">
                            <div>
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item->label }}</span>
                                <span class="ml-2 text-xs text-zinc-400">{{ $item->url }}</span>
                            </div>
                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="deleteItem({{ $item->id }})" />
                        </div>
                    @endforeach
                </div>
            @endif

            <flux:button size="sm" variant="ghost" icon="plus" wire:click="openItemModal({{ $menu->id }})">Add item</flux:button>
        </div>
    @endforeach

    @if ($menus->isEmpty())
        <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-zinc-400">No navigation menus yet.</flux:text>
        </div>
    @endif

    {{-- Menu modal --}}
    <flux:modal name="menu-form" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingMenuId ? 'Edit menu' : 'Add menu' }}</flux:heading>
            <flux:input wire:model="menuTitle" label="Title" placeholder="Main Menu" />
            <flux:input wire:model="menuHandle" label="Handle" placeholder="main-menu" />
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('menu-form').close()">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveMenu">Save</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Item modal --}}
    <flux:modal name="item-form" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Add item</flux:heading>
            <flux:input wire:model="itemLabel" label="Label" placeholder="Home" />
            <flux:input wire:model="itemUrl" label="URL" placeholder="/about" />
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('item-form').close()">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveItem">Add</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
