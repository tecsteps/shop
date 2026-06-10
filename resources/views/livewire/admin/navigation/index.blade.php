<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Navigation')]]" />

    <flux:heading size="xl" level="1">{{ __('Navigation') }}</flux:heading>

    {{-- Menu list --}}
    @if ($this->menus->isEmpty())
        <x-admin.card class="flex flex-col items-center gap-3 py-16 text-center">
            <flux:icon name="bars-3" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">{{ __('No menus yet') }}</flux:heading>
            <flux:text>{{ __('Navigation menus are created when the store is set up.') }}</flux:text>
        </x-admin.card>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ($this->menus as $menu)
                <x-admin.card wire:key="menu-{{ $menu->id }}" class="flex items-center justify-between gap-3 !p-4">
                    <div>
                        <flux:heading>{{ $menu->title }}</flux:heading>
                        <flux:text class="text-sm">
                            {{ trans_choice(':count item|:count items', $menu->items_count, ['count' => $menu->items_count]) }}
                            - {{ $menu->handle }}
                        </flux:text>
                    </div>
                    <flux:button
                        :variant="$editingMenuId === $menu->id ? 'primary' : 'ghost'"
                        size="sm"
                        wire:click="selectMenu({{ $menu->id }})"
                        data-test="edit-menu-{{ $menu->id }}"
                    >
                        {{ __('Edit') }}
                    </flux:button>
                </x-admin.card>
            @endforeach
        </div>
    @endif

    {{-- Menu editor --}}
    @if ($editingMenuId !== null)
        @php($menu = $this->menus->firstWhere('id', $editingMenuId))
        <x-admin.card class="space-y-4" data-test="menu-editor">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:heading>{{ $menu?->title }}</flux:heading>

                @can('update', $menu)
                    <flux:button variant="ghost" size="sm" icon="plus" wire:click="addItem" data-test="add-item-button">
                        {{ __('Add item') }}
                    </flux:button>
                @endcan
            </div>

            @if ($menuItems === [])
                <flux:text>{{ __('This menu has no items yet.') }}</flux:text>
            @else
                <div class="space-y-2" wire:sort="reorderItems">
                    @foreach ($menuItems as $index => $item)
                        <div
                            wire:key="menu-item-{{ $index }}-{{ $item['id'] ?? 'new' }}"
                            wire:sort.item="{{ $index }}"
                            class="flex cursor-grab items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
                        >
                            <flux:icon name="bars-3" variant="micro" class="shrink-0 text-zinc-400" />

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $item['label'] }}</p>
                                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $this->describeItem($item) }}</p>
                            </div>

                            @can('update', $menu)
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="pencil"
                                    wire:click="editItem({{ $index }})"
                                    aria-label="{{ __('Edit :label', ['label' => $item['label']]) }}"
                                    data-test="edit-item-{{ $index }}"
                                />
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    wire:click="removeItem({{ $index }})"
                                    aria-label="{{ __('Remove :label', ['label' => $item['label']]) }}"
                                    data-test="remove-item-{{ $index }}"
                                />
                            @endcan
                        </div>
                    @endforeach
                </div>
            @endif

            @can('update', $menu)
                <div class="flex justify-end">
                    <flux:button variant="primary" wire:click="saveMenu" data-test="save-menu-button">
                        <span wire:loading.remove wire:target="saveMenu">{{ __('Save menu') }}</span>
                        <span wire:loading wire:target="saveMenu">{{ __('Saving...') }}</span>
                    </flux:button>
                </div>
            @endcan
        </x-admin.card>
    @endif

    {{-- Item form modal --}}
    <flux:modal name="item-form" class="md:max-w-md">
        <form wire:submit="saveItem" class="space-y-4">
            <flux:heading size="lg">
                {{ $editingItemIndex !== null ? __('Edit menu item') : __('Add menu item') }}
            </flux:heading>

            <flux:field>
                <flux:label>{{ __('Label') }}</flux:label>
                <flux:input wire:model="itemLabel" :placeholder="__('About Us')" data-test="item-label-input" />
                <flux:error name="itemLabel" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Type') }}</flux:label>
                <flux:select wire:model.live="itemType" data-test="item-type-select">
                    <flux:select.option value="link">{{ __('Custom link') }}</flux:select.option>
                    <flux:select.option value="page">{{ __('Page') }}</flux:select.option>
                    <flux:select.option value="collection">{{ __('Collection') }}</flux:select.option>
                    <flux:select.option value="product">{{ __('Product') }}</flux:select.option>
                </flux:select>
                <flux:error name="itemType" />
            </flux:field>

            @if ($itemType === 'link')
                <flux:field>
                    <flux:label>{{ __('URL') }}</flux:label>
                    <flux:input wire:model="itemUrl" placeholder="https://..." data-test="item-url-input" />
                    <flux:error name="itemUrl" />
                </flux:field>
            @else
                <flux:field>
                    <flux:label>
                        {{ match ($itemType) {
                            'page' => __('Page'),
                            'collection' => __('Collection'),
                            default => __('Product'),
                        } }}
                    </flux:label>
                    <flux:select wire:model="itemResourceId" data-test="item-resource-select">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach (match ($itemType) {
                            'page' => $this->availablePages,
                            'collection' => $this->availableCollections,
                            default => $this->availableProducts,
                        } as $resource)
                            <flux:select.option value="{{ $resource['id'] }}">{{ $resource['title'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="itemResourceId" />
                </flux:field>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" data-test="save-item-button">
                    {{ __('Save item') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
