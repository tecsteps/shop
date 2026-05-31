<div>
    <x-admin.breadcrumbs :items="[['label' => __('Navigation')]]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ __('Navigation') }}</flux:heading>

    {{-- Menu list. --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($this->menus as $menu)
            <x-admin.card wire:key="menu-{{ $menu->id }}">
                <div class="flex items-center justify-between">
                    <flux:heading size="md">{{ $menu->title }}</flux:heading>
                    <flux:button size="sm" variant="ghost" wire:click="selectMenu({{ $menu->id }})" data-test="edit-menu-{{ $menu->id }}">{{ __('Edit') }}</flux:button>
                </div>
            </x-admin.card>
        @endforeach
    </div>

    {{-- Menu editor. --}}
    @if ($editingMenuId !== null)
        <x-admin.card>
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="md">{{ __('Menu items') }}</flux:heading>
                <flux:button size="sm" variant="ghost" icon="plus" wire:click="addItem" data-test="add-item">{{ __('Add item') }}</flux:button>
            </div>

            @if (count($menuItems) > 0)
                <ul class="space-y-2" wire:sort="sortItems">
                    @foreach ($menuItems as $index => $item)
                        <li class="flex items-center gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700"
                            wire:key="item-{{ $item['id'] ?? 'new'.$index }}"
                            @if (! empty($item['id'])) wire:sort:item="{{ $item['id'] }}" @endif>
                            <div wire:sort:handle class="cursor-grab text-zinc-400"><flux:icon.bars-3 class="size-4" /></div>
                            <div class="flex-1">
                                <div class="text-sm font-medium">{{ $item['label'] }}</div>
                                <div class="text-xs text-zinc-500">{{ $item['type'] }}: {{ $item['type'] === 'link' ? $item['url'] : ('#'.$item['resource_id']) }}</div>
                            </div>
                            <div wire:sort:ignore class="flex gap-1">
                                <flux:button size="xs" variant="ghost" icon="pencil" wire:click="editItem({{ $index }})" :aria-label="__('Edit item')" />
                                <flux:button size="xs" variant="ghost" icon="trash" wire:click="removeItem({{ $index }})" :aria-label="__('Remove item')" />
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <flux:text class="text-sm">{{ __('No items yet. Add one to build the menu.') }}</flux:text>
            @endif

            <div class="mt-4">
                <flux:button variant="primary" wire:click="saveMenu" data-test="save-menu">{{ __('Save menu') }}</flux:button>
            </div>
        </x-admin.card>
    @endif

    {{-- Item form modal. --}}
    <flux:modal wire:model.self="showItemModal" name="item-form" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingItemIndex !== null ? __('Edit menu item') : __('Add menu item') }}</flux:heading>
            <flux:field>
                <flux:label>{{ __('Label') }}</flux:label>
                <flux:input wire:model="itemLabel" placeholder="About Us" data-test="item-label" />
                <flux:error name="itemLabel" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('Type') }}</flux:label>
                <flux:select wire:model.live="itemType">
                    <flux:select.option value="link">{{ __('Custom link') }}</flux:select.option>
                    <flux:select.option value="page">{{ __('Page') }}</flux:select.option>
                    <flux:select.option value="collection">{{ __('Collection') }}</flux:select.option>
                    <flux:select.option value="product">{{ __('Product') }}</flux:select.option>
                </flux:select>
            </flux:field>

            @if ($itemType === 'link')
                <flux:field>
                    <flux:label>{{ __('URL') }}</flux:label>
                    <flux:input wire:model="itemUrl" placeholder="https://..." />
                    <flux:error name="itemUrl" />
                </flux:field>
            @else
                <flux:field>
                    <flux:label>{{ __('Resource') }}</flux:label>
                    <flux:select wire:model="itemResourceId">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach ($this->resourceOptions as $option)
                            <flux:select.option :value="$option['id']">{{ $option['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="itemResourceId" />
                </flux:field>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showItemModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="saveItem" data-test="save-item">{{ __('Save item') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
