<div>
    <flux:heading size="xl" class="mb-6">Navigation</flux:heading>

    {{-- Menu cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        @forelse ($menus as $menu)
            <div
                wire:key="menu-{{ $menu->id }}"
                class="flex items-center justify-between p-4 border rounded-lg {{ $editingMenuId === $menu->id ? 'border-zinc-900 dark:border-white bg-zinc-50 dark:bg-zinc-800' : 'border-zinc-200 dark:border-zinc-700' }}"
            >
                <div>
                    <flux:heading size="md">{{ $menu->name }}</flux:heading>
                    <flux:text class="mt-1">{{ $menu->handle }}</flux:text>
                </div>
                <flux:button size="sm" wire:click="selectMenu({{ $menu->id }})">
                    {{ $editingMenuId === $menu->id ? 'Editing' : 'Edit' }}
                </flux:button>
            </div>
        @empty
            <div class="col-span-2 text-center py-8">
                <flux:text>No navigation menus found.</flux:text>
            </div>
        @endforelse
    </div>

    {{-- Menu editor --}}
    @if ($editingMenuId)
        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="lg">
                    {{ $menus->firstWhere('id', $editingMenuId)?->name ?? 'Menu' }}
                </flux:heading>
                <flux:button size="sm" variant="ghost" wire:click="addItem">
                    <flux:icon name="plus" class="size-4 mr-1" /> Add item
                </flux:button>
            </div>

            <div class="p-4">
                @if (!empty($menuItems))
                    <div class="space-y-2">
                        @foreach ($menuItems as $index => $item)
                            <div
                                wire:key="item-{{ $index }}"
                                class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col gap-0.5">
                                        <button wire:click="moveItemUp({{ $index }})" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 {{ $index === 0 ? 'opacity-30 cursor-not-allowed' : '' }}" @if($index === 0) disabled @endif>
                                            <flux:icon name="chevron-up" class="size-3" />
                                        </button>
                                        <button wire:click="moveItemDown({{ $index }})" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 {{ $index === count($menuItems) - 1 ? 'opacity-30 cursor-not-allowed' : '' }}" @if($index === count($menuItems) - 1) disabled @endif>
                                            <flux:icon name="chevron-down" class="size-3" />
                                        </button>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item['title'] }}</span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 ml-2">
                                            {{ $item['type'] }}{{ $item['url'] ? ': ' . $item['url'] : '' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <flux:button size="sm" variant="ghost" wire:click="editItem({{ $index }})">
                                        <flux:icon name="pencil" class="size-4" />
                                    </flux:button>
                                    <flux:button size="sm" variant="ghost" wire:click="removeItem({{ $index }})" wire:confirm="Remove this menu item?">
                                        <flux:icon name="trash" class="size-4 text-red-500" />
                                    </flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <flux:text>No items in this menu. Click "Add item" to get started.</flux:text>
                    </div>
                @endif

                <div class="mt-4 flex justify-end">
                    <flux:button variant="primary" wire:click="saveMenu" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveMenu">Save menu</span>
                        <span wire:loading wire:target="saveMenu">Saving...</span>
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- Item form modal --}}
    <flux:modal wire:model="showItemModal" name="item-form" class="max-w-md">
        <form wire:submit="saveItem" class="space-y-4">
            <flux:heading size="lg">{{ $editingItemIndex !== null ? 'Edit menu item' : 'Add menu item' }}</flux:heading>

            <flux:input
                wire:model="itemLabel"
                label="Label"
                placeholder="About Us"
                required
            />
            @error('itemLabel')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <flux:select wire:model.live="itemType" label="Type">
                <option value="link">Custom link</option>
                <option value="page">Page</option>
                <option value="collection">Collection</option>
                <option value="product">Product</option>
            </flux:select>

            @if ($itemType === 'link')
                <flux:input
                    wire:model="itemUrl"
                    label="URL"
                    placeholder="https://..."
                />
            @elseif ($itemType === 'page')
                <flux:select wire:model="itemResourceId" label="Page">
                    <option value="">Select a page</option>
                    @foreach ($availablePages as $pg)
                        <option value="{{ $pg->id }}">{{ $pg->title }}</option>
                    @endforeach
                </flux:select>
            @elseif ($itemType === 'collection')
                <flux:select wire:model="itemResourceId" label="Collection">
                    <option value="">Select a collection</option>
                    @foreach ($availableCollections as $col)
                        <option value="{{ $col->id }}">{{ $col->title }}</option>
                    @endforeach
                </flux:select>
            @elseif ($itemType === 'product')
                <flux:select wire:model="itemResourceId" label="Product">
                    <option value="">Select a product</option>
                    @foreach ($availableProducts as $prod)
                        <option value="{{ $prod->id }}">{{ $prod->title }}</option>
                    @endforeach
                </flux:select>
            @endif

            <div class="flex justify-end gap-3 pt-4">
                <flux:button variant="ghost" wire:click="$set('showItemModal', false)">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save item</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
