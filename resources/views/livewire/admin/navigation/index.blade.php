<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Navigation</flux:heading>

        @can('manage-navigation')
            <flux:button variant="primary" icon="plus" wire:click="openMenuForm">Create menu</flux:button>
        @endcan
    </div>

    {{-- Menu selector cards (spec 03 §14) --}}
    @if ($menus->isEmpty())
        <div class="flex flex-col items-center rounded-lg border border-zinc-200 bg-white px-6 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="bars-3" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg" class="mt-4">Create your first menu</flux:heading>
            <flux:text class="mt-1">Menus like "Main menu" and "Footer menu" structure your storefront navigation.</flux:text>
            @can('manage-navigation')
                <flux:button variant="primary" class="mt-6" wire:click="openMenuForm">Create menu</flux:button>
            @endcan
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($menus as $menu)
                <button
                    type="button"
                    wire:key="menu-{{ $menu->id }}"
                    wire:click="selectMenu({{ $menu->id }})"
                    class="rounded-lg border p-4 text-left transition dark:bg-zinc-900 {{ $selectedMenuId === $menu->id ? 'border-blue-500 ring-2 ring-blue-500/30 dark:border-blue-500' : 'border-zinc-200 bg-white hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600' }}"
                >
                    <span class="block font-medium text-zinc-900 dark:text-zinc-100">{{ $menu->title }}</span>
                    <span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">{{ $menu->handle }} &middot; {{ $menu->items_count }} {{ Str::plural('item', $menu->items_count) }}</span>
                </button>
            @endforeach
        </div>

        {{-- Menu editor (spec 03 §14) --}}
        @if ($selectedMenu !== null)
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4">
                    <flux:heading size="lg">{{ $selectedMenu->title }}</flux:heading>

                    @can('manage-navigation')
                        <flux:button variant="ghost" icon="plus" wire:click="openItemForm">Add item</flux:button>
                    @endcan
                </div>

                @if ($items->isEmpty())
                    <flux:text class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">No items yet. Add links, pages, collections, or products.</flux:text>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($items as $index => $item)
                            <li wire:key="item-{{ $item->id }}" class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                <div class="flex-1">
                                    <span class="block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item->label }}</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $item->type->value }}:
                                        @if ($item->type === \App\Enums\NavigationItemType::Link)
                                            {{ $item->url }}
                                        @else
                                            {{ $resourceLabels[$item->type->value][$item->resource_id] ?? 'Missing resource' }}
                                        @endif
                                    </span>
                                </div>

                                @can('manage-navigation')
                                    {{-- Reorder via buttons instead of drag-and-drop (spec 03 §14 note) --}}
                                    <flux:button size="sm" variant="ghost" icon="chevron-up" wire:click="moveItem({{ $item->id }}, 'up')" :disabled="$index === 0" aria-label="Move {{ $item->label }} up" />
                                    <flux:button size="sm" variant="ghost" icon="chevron-down" wire:click="moveItem({{ $item->id }}, 'down')" :disabled="$index === $items->count() - 1" aria-label="Move {{ $item->label }} down" />
                                    <flux:button size="sm" variant="ghost" icon="pencil" wire:click="openItemForm({{ $item->id }})" aria-label="Edit {{ $item->label }}" />
                                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $item->id }})" aria-label="Remove {{ $item->label }}" />
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    @endif

    {{-- New menu modal --}}
    <flux:modal wire:model="showMenuForm" name="menu-form" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">Create menu</flux:heading>

            <flux:field>
                <flux:label for="menuTitle">Title</flux:label>
                <flux:input id="menuTitle" wire:model.blur="menuTitle" placeholder="Main menu" />
                <flux:error name="menuTitle" />
            </flux:field>

            <flux:field>
                <flux:label for="menuHandle">Handle</flux:label>
                <flux:input id="menuHandle" wire:model.blur="menuHandle" placeholder="main-menu" />
                <flux:error name="menuHandle" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showMenuForm', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="createMenu">Create menu</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Menu item form modal (spec 03 §14) --}}
    <flux:modal wire:model="showItemForm" name="item-form" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $editingItemId === null ? 'Add menu item' : 'Edit menu item' }}</flux:heading>

            <flux:field>
                <flux:label for="itemLabel">Label</flux:label>
                <flux:input id="itemLabel" wire:model.blur="itemLabel" placeholder="About Us" />
                <flux:error name="itemLabel" />
            </flux:field>

            <flux:field>
                <flux:label for="itemType">Type</flux:label>
                <flux:select id="itemType" wire:model.live="itemType">
                    <flux:select.option value="link">Custom link</flux:select.option>
                    <flux:select.option value="page">Page</flux:select.option>
                    <flux:select.option value="collection">Collection</flux:select.option>
                    <flux:select.option value="product">Product</flux:select.option>
                </flux:select>
                <flux:error name="itemType" />
            </flux:field>

            @if ($itemType === 'link')
                <flux:field>
                    <flux:label for="itemUrl">URL</flux:label>
                    <flux:input id="itemUrl" wire:model.blur="itemUrl" placeholder="https://..." />
                    <flux:error name="itemUrl" />
                </flux:field>
            @else
                <flux:field>
                    <flux:label for="itemResourceId">{{ ucfirst($itemType) }}</flux:label>
                    @php($resources = match ($itemType) {
                        'page' => $pages,
                        'collection' => $collections,
                        default => $products,
                    })
                    <flux:select id="itemResourceId" wire:model="itemResourceId">
                        <flux:select.option value="">Select a {{ $itemType }}...</flux:select.option>
                        @foreach ($resources as $resource)
                            <flux:select.option value="{{ $resource->id }}">{{ $resource->title }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="itemResourceId" />
                </flux:field>
            @endif

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('showItemForm', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveItem">Save item</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
