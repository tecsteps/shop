<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Navigation</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Navigation</flux:heading>

    <flux:heading size="md">Menus</flux:heading>
    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($menus as $menu)
            <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" wire:key="menu-{{ $menu->getKey() }}">
                <div>
                    <p class="font-medium">{{ $menu->title }}</p>
                    <p class="text-xs text-zinc-500">{{ $menu->handle }}</p>
                </div>
                <flux:button variant="ghost" size="sm" wire:click="selectMenu({{ $menu->getKey() }})">Edit</flux:button>
            </div>
        @endforeach
    </div>

    @if ($editingMenu !== null)
        <div class="mt-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:heading size="md">{{ $editingMenu->title }}</flux:heading>
                @can('manage', App\Models\NavigationMenu::class)
                    <flux:button variant="ghost" size="sm" wire:click="addItem">
                        <flux:icon.plus class="size-4" variant="outline" /> Add item
                    </flux:button>
                @endcan
            </div>

            <div class="mt-4 flex flex-col gap-2">
                @forelse ($this->menuItems as $index => $item)
                    <div class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700" wire:key="item-{{ $index }}">
                        <flux:icon.bars-2 class="size-4 shrink-0 text-zinc-400" variant="outline" />
                        <div class="flex-1">
                            <p class="text-sm font-semibold">{{ $item['label'] }}</p>
                            <p class="text-xs text-zinc-500">{{ $item['type'] }}: {{ $item['type'] === 'link' ? $item['url'] : '#'.$item['resource_id'] }}</p>
                        </div>
                        @can('manage', App\Models\NavigationMenu::class)
                            <flux:button variant="ghost" size="sm" wire:click="moveItemUp({{ $index }})" aria-label="Move up">
                                <flux:icon.chevron-up class="size-4" variant="outline" />
                            </flux:button>
                            <flux:button variant="ghost" size="sm" wire:click="moveItemDown({{ $index }})" aria-label="Move down">
                                <flux:icon.chevron-down class="size-4" variant="outline" />
                            </flux:button>
                            <flux:button variant="ghost" size="sm" wire:click="editItem({{ $index }})" aria-label="Edit item">
                                <flux:icon.pencil class="size-4" variant="outline" />
                            </flux:button>
                            <flux:button variant="ghost" size="sm" wire:click="removeItem({{ $index }})" aria-label="Remove item">
                                <flux:icon.trash class="size-4" variant="outline" />
                            </flux:button>
                        @endcan
                    </div>
                @empty
                    <flux:text variant="subtle">No items in this menu yet.</flux:text>
                @endforelse
            </div>

            @can('manage', App\Models\NavigationMenu::class)
                <div class="mt-4 flex justify-end">
                    <flux:button variant="primary" wire:click="saveMenu">Save menu</flux:button>
                </div>
            @endcan
        </div>
    @endif

    <flux:modal wire:model="showItemModal" class="max-w-md">
        <flux:heading size="lg">{{ $this->editingItemIndex !== null ? 'Edit menu item' : 'Add menu item' }}</flux:heading>
        <flux:input wire:model="itemLabel" label="Label" placeholder="About Us" class="mt-4" />
        <flux:select wire:model.live="itemType" label="Type" class="mt-3">
            <flux:select.option value="link">Custom link</flux:select.option>
            <flux:select.option value="page">Page</flux:select.option>
            <flux:select.option value="collection">Collection</flux:select.option>
            <flux:select.option value="product">Product</flux:select.option>
        </flux:select>
        @if ($this->itemType === 'link')
            <flux:input wire:model="itemUrl" label="URL" type="url" placeholder="https://..." class="mt-3" />
        @else
            <flux:select wire:model="itemResourceId" label="Resource" class="mt-3">
                <flux:select.option value="">Select...</flux:select.option>
                @foreach ($resources as $resource)
                    <flux:select.option value="{{ $resource->getKey() }}">{{ $resource->title }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showItemModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveItem">Save item</flux:button>
        </div>
    </flux:modal>
</div>
