<div>
    <flux:heading size="xl">Navigation</flux:heading>

    {{-- Menus --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->menus as $menu)
            <flux:card class="p-5">
                <div class="flex items-center justify-between gap-3">
                    <flux:heading size="md">{{ $menu->title }}</flux:heading>
                    <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="selectMenu({{ $menu->id }})">
                        Edit
                    </flux:button>
                </div>
                <flux:text class="mt-1">{{ $menu->items->count() }} item(s)</flux:text>
            </flux:card>
        @empty
            <flux:card class="p-5 sm:col-span-2 lg:col-span-3">
                <flux:text>No navigation menus configured.</flux:text>
            </flux:card>
        @endforelse
    </div>

    {{-- Menu editor --}}
    @if ($selectedMenuId !== null)
        <flux:card class="mt-6 p-6">
            @php $selectedMenu = $this->menus->firstWhere('id', $selectedMenuId); @endphp

            <div class="flex items-center justify-between gap-3">
                <flux:heading size="lg">{{ $selectedMenu?->title }}</flux:heading>
                <flux:button variant="ghost" size="sm" icon="plus" wire:click="addItem">Add item</flux:button>
            </div>

            <div class="mt-4 space-y-2">
                @forelse ($menuItems as $index => $item)
                    @php
                        $target = match ($item['type']) {
                            'page' => 'page: '.($this->availablePages->firstWhere('id', $item['resource_id'])?->title ?? 'Unknown'),
                            'collection' => 'collection: '.($this->availableCollections->firstWhere('id', $item['resource_id'])?->title ?? 'Unknown'),
                            'product' => 'product: '.($this->availableProducts->firstWhere('id', $item['resource_id'])?->title ?? 'Unknown'),
                            default => 'link: '.($item['url'] ?: '/'),
                        };
                    @endphp
                    <div class="flex items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                        <flux:icon.bars-3 class="size-4 shrink-0 cursor-grab text-zinc-400" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-zinc-800 dark:text-white">{{ $item['label'] }}</p>
                            <p class="truncate text-xs text-zinc-400">{{ $target }}</p>
                        </div>
                        <div class="flex items-center gap-0.5">
                            <flux:button variant="ghost" size="sm" icon="chevron-up" wire:click="moveItem({{ $index }}, -1)" aria-label="Move up" />
                            <flux:button variant="ghost" size="sm" icon="chevron-down" wire:click="moveItem({{ $index }}, 1)" aria-label="Move down" />
                            <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="editItem({{ $index }})" aria-label="Edit item" />
                            <flux:button variant="ghost" size="sm" icon="trash" wire:click="removeItem({{ $index }})" aria-label="Delete item" />
                        </div>
                    </div>
                @empty
                    <flux:text>No items yet. Add your first menu item.</flux:text>
                @endforelse
            </div>

            <div class="mt-4 flex justify-end">
                <flux:button variant="primary" wire:click="saveMenu" wire:loading.attr="disabled">Save menu</flux:button>
            </div>
        </flux:card>
    @endif

    {{-- Item modal --}}
    <flux:modal wire:model="showItemModal" class="max-w-md">
        <flux:heading size="lg">{{ $editingItemIndex !== null ? 'Edit menu item' : 'Add menu item' }}</flux:heading>

        <div class="mt-4 space-y-4">
            <flux:field>
                <flux:label>Label</flux:label>
                <flux:input wire:model="itemLabel" placeholder="About Us" />
                <flux:error name="itemLabel" />
            </flux:field>

            <flux:field>
                <flux:label>Type</flux:label>
                <flux:select wire:model="itemType">
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
                    <flux:error name="itemUrl" />
                </flux:field>
            @elseif ($itemType === 'page')
                <flux:field>
                    <flux:label>Page</flux:label>
                    <flux:select wire:model="itemResourceId">
                        @foreach ($this->availablePages as $page)
                            <option value="{{ $page->id }}">{{ $page->title }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @elseif ($itemType === 'collection')
                <flux:field>
                    <flux:label>Collection</flux:label>
                    <flux:select wire:model="itemResourceId">
                        @foreach ($this->availableCollections as $collection)
                            <option value="{{ $collection->id }}">{{ $collection->title }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @else
                <flux:field>
                    <flux:label>Product</flux:label>
                    <flux:select wire:model="itemResourceId">
                        @foreach ($this->availableProducts as $product)
                            <option value="{{ $product->id }}">{{ $product->title }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            @endif
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('showItemModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveItem">Save item</flux:button>
        </div>
    </flux:modal>
</div>
