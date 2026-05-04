<section class="space-y-6">
    <div>
        <flux:heading size="xl">Navigation</flux:heading>
        <flux:text class="mt-1">Storefront menus and ordered links.</flux:text>
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <div class="grid gap-6 xl:grid-cols-[280px_1fr_360px]">
        <div class="space-y-3">
            @foreach ($menus as $menu)
                <button
                    type="button"
                    wire:click="selectMenu({{ $menu->getKey() }})"
                    wire:key="navigation-menu-{{ $menu->getKey() }}"
                    class="block w-full rounded-lg border px-4 py-3 text-left text-sm {{ $selectedMenuId === $menu->getKey() ? 'border-blue-500 bg-blue-50 text-blue-950 dark:border-blue-400 dark:bg-blue-950/40 dark:text-blue-100' : 'border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                >
                    <span class="font-medium">{{ $menu->title }}</span>
                    <span class="mt-1 block text-xs opacity-70">{{ $menu->handle }}</span>
                </button>
            @endforeach
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-4 border-b border-zinc-200 p-5 dark:border-zinc-700">
                <div>
                    <flux:heading size="lg">{{ $selectedMenu->title }}</flux:heading>
                    <flux:text class="mt-1">{{ count($menuItems) }} items</flux:text>
                </div>

                <flux:button type="button" wire:click="addItem" variant="filled" icon="plus">Add item</flux:button>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-800" wire:sort="reorderItem" wire:sort:group="navigation-items" wire:sort:group-id="root">
                @forelse ($navigationTree as $item)
                    <div wire:key="navigation-item-{{ $item['key'] }}" wire:sort:item="{{ $item['key'] }}" class="p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <div class="flex flex-1 items-center gap-3">
                                <div class="flex items-center gap-1">
                                    <span wire:sort:handle class="flex size-8 cursor-grab items-center justify-center rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                                        <flux:icon name="bars-3" class="size-4" />
                                    </span>
                                    <flux:button type="button" wire:click="moveItemUp({{ $item['index'] }})" size="sm" variant="ghost" icon="arrow-up" aria-label="Move {{ $item['label'] }} up" />
                                    <flux:button type="button" wire:click="moveItemDown({{ $item['index'] }})" size="sm" variant="ghost" icon="arrow-down" aria-label="Move {{ $item['label'] }} down" />
                                </div>

                                <div>
                                    <div class="font-medium text-zinc-950 dark:text-white">{{ $item['label'] }}</div>
                                    <div class="text-xs text-zinc-500">{{ $this->targetLabel($item) }}</div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2" wire:sort:ignore>
                                <flux:button type="button" wire:click="editItem({{ $item['index'] }})" size="sm" variant="filled">Edit</flux:button>
                                <flux:button type="button" wire:click="removeItem({{ $item['index'] }})" size="sm" variant="danger">Remove</flux:button>
                            </div>
                        </div>

                        @if ($item['children'] !== [])
                            <div class="mt-3 space-y-2 border-l border-zinc-200 pl-5 dark:border-zinc-700" wire:sort="reorderItem" wire:sort:group="navigation-items" wire:sort:group-id="{{ $item['key'] }}">
                                @foreach ($item['children'] as $child)
                                    <div wire:key="navigation-item-{{ $child['key'] }}" wire:sort:item="{{ $child['key'] }}" class="flex flex-col gap-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-950 sm:flex-row sm:items-center">
                                        <div class="flex flex-1 items-center gap-3">
                                            <div class="flex items-center gap-1">
                                                <span wire:sort:handle class="flex size-8 cursor-grab items-center justify-center rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                                                    <flux:icon name="bars-3" class="size-4" />
                                                </span>
                                                <flux:button type="button" wire:click="moveItemUp({{ $child['index'] }})" size="sm" variant="ghost" icon="arrow-up" aria-label="Move {{ $child['label'] }} up" />
                                                <flux:button type="button" wire:click="moveItemDown({{ $child['index'] }})" size="sm" variant="ghost" icon="arrow-down" aria-label="Move {{ $child['label'] }} down" />
                                            </div>

                                            <div>
                                                <div class="font-medium text-zinc-950 dark:text-white">{{ $child['label'] }}</div>
                                                <div class="text-xs text-zinc-500">{{ $this->targetLabel($child) }}</div>
                                            </div>
                                        </div>

                                        <div class="flex justify-end gap-2" wire:sort:ignore>
                                            <flux:button type="button" wire:click="editItem({{ $child['index'] }})" size="sm" variant="filled">Edit</flux:button>
                                            <flux:button type="button" wire:click="removeItem({{ $child['index'] }})" size="sm" variant="danger">Remove</flux:button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center text-zinc-500">No menu items.</div>
                @endforelse
            </div>

            <div class="flex justify-end border-t border-zinc-200 p-5 dark:border-zinc-700">
                <flux:button type="button" wire:click="saveMenu" variant="primary">Save menu</flux:button>
            </div>
        </div>

        <form wire:submit="saveItem" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ $editingItemIndex === null ? 'Add item' : 'Edit item' }}</flux:heading>

            <div class="mt-4 space-y-4">
                <flux:input wire:model="itemLabel" label="Label" />
                <flux:error name="itemLabel" />

                <flux:select wire:model="itemParentKey" label="Parent item">
                    <flux:select.option value="">Top level</flux:select.option>
                    @foreach ($parentOptions as $parent)
                        <flux:select.option value="{{ $parent['key'] }}">{{ $parent['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="itemParentKey" />

                <flux:select wire:model.live="itemType" label="Type">
                    <flux:select.option value="link">Custom link</flux:select.option>
                    <flux:select.option value="page">Page</flux:select.option>
                    <flux:select.option value="collection">Collection</flux:select.option>
                    <flux:select.option value="product">Product</flux:select.option>
                </flux:select>

                @if ($itemType === 'link')
                    <flux:input wire:model="itemUrl" label="URL" placeholder="/collections" />
                    <flux:error name="itemUrl" />
                @else
                    <flux:select wire:model="itemResourceId" label="Resource">
                        <flux:select.option value="">Select resource</flux:select.option>
                        @foreach ($resources as $resource)
                            <flux:select.option value="{{ $resource->getKey() }}">{{ $resource->title }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="itemResourceId" />
                @endif

                <div class="flex justify-end gap-2">
                    <flux:button type="button" wire:click="cancelItem" variant="ghost">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">{{ $editingItemIndex === null ? 'Add item' : 'Update item' }}</flux:button>
                </div>
            </div>
        </form>
    </div>
</section>
