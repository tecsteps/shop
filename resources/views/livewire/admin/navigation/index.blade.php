<div>
    <div class="mb-6">
        <flux:heading size="xl">Navigation</flux:heading>
    </div>

    {{-- Menu List --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($this->menus as $menu)
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900" wire:key="menu-{{ $menu->id }}">
                <flux:heading size="md">{{ $menu->title }}</flux:heading>
                <flux:button wire:click="selectMenu({{ $menu->id }})">Edit</flux:button>
            </div>
        @endforeach
    </div>

    {{-- Menu Editor --}}
    @if ($editingMenu)
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ $editingMenu->title }}</flux:heading>
                <flux:button variant="ghost" wire:click="addItem" size="sm">
                    <flux:icon name="plus" variant="mini" class="mr-1 h-4 w-4" />
                    Add item
                </flux:button>
            </div>

            <div class="space-y-2">
                @foreach ($menuItems as $index => $item)
                    <div class="flex items-center gap-3 rounded border border-gray-200 px-4 py-3 dark:border-gray-700" wire:key="item-{{ $index }}">
                        <flux:icon name="bars-3" variant="outline" class="h-4 w-4 text-gray-400" />
                        <div class="flex-1">
                            <span class="font-medium">{{ $item['label'] }}</span>
                            <span class="ml-2 text-xs text-gray-500">
                                {{ $item['type'] }}: {{ $item['url'] ?: 'resource #'.$item['resource_id'] }}
                            </span>
                        </div>
                        <flux:button variant="ghost" size="sm" wire:click="editItem({{ $index }})" icon="pencil" />
                        <flux:button variant="ghost" size="sm" wire:click="removeItem({{ $index }})" icon="trash" class="text-red-500" />
                    </div>
                @endforeach
            </div>

            @if (empty($menuItems))
                <flux:text class="py-4 text-center text-gray-500">No items yet. Add your first menu item.</flux:text>
            @endif

            <div class="mt-4 flex justify-end">
                <flux:button variant="primary" wire:click="saveMenu">Save menu</flux:button>
            </div>
        </div>
    @endif

    {{-- Item Form Modal --}}
    <flux:modal name="item-form" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingItemIndex !== null ? 'Edit menu item' : 'Add menu item' }}</flux:heading>
            <flux:field>
                <flux:label>Label</flux:label>
                <flux:input wire:model="itemLabel" placeholder="About Us" />
            </flux:field>
            <flux:field>
                <flux:label>Type</flux:label>
                <flux:select wire:model.live="itemType">
                    <option value="link">Custom link</option>
                    <option value="page">Page</option>
                    <option value="collection">Collection</option>
                    <option value="product">Product</option>
                </flux:select>
            </flux:field>

            @if ($itemType === 'link')
                <flux:field>
                    <flux:label>URL</flux:label>
                    <flux:input wire:model="itemUrl" placeholder="https://..." />
                </flux:field>
            @elseif ($itemType === 'page')
                <flux:field>
                    <flux:label>Page</flux:label>
                    <flux:select wire:model="itemResourceId">
                        <option value="">Select a page</option>
                        @foreach ($this->availablePages as $page)
                            <option value="{{ $page->id }}">{{ $page->title }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @elseif ($itemType === 'collection')
                <flux:field>
                    <flux:label>Collection</flux:label>
                    <flux:select wire:model="itemResourceId">
                        <option value="">Select a collection</option>
                        @foreach ($this->availableCollections as $collection)
                            <option value="{{ $collection->id }}">{{ $collection->title }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @elseif ($itemType === 'product')
                <flux:field>
                    <flux:label>Product</flux:label>
                    <flux:select wire:model="itemResourceId">
                        <option value="">Select a product</option>
                        @foreach ($this->availableProducts as $product)
                            <option value="{{ $product->id }}">{{ $product->title }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" x-on:click="$flux.modal('item-form').close()">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveItem">Save item</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
