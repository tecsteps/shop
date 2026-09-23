<div>
    @include('livewire.admin.components.page-heading', ['title' => 'Navigation', 'breadcrumbs' => [['label' => 'Navigation']]])

    <div class="grid gap-6 xl:grid-cols-[17rem_minmax(0,1fr)]">
        <section aria-labelledby="navigation-menus-heading" class="h-fit rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 id="navigation-menus-heading" class="text-base font-semibold">Menus</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Choose a menu to manage its links.</p>
            <div class="mt-4 grid gap-2">
                @foreach ($menus as $menu)
                    <button type="button" wire:click="selectMenu({{ $menu->id }})" wire:key="navigation-menu-{{ $menu->id }}" @class([
                        'flex min-h-14 items-center justify-between gap-3 rounded-xl border px-4 text-left transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600',
                        'border-blue-300 bg-blue-50 text-blue-950 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-100' => $selectedMenu?->id === $menu->id,
                        'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800' => $selectedMenu?->id !== $menu->id,
                    ])>
                        <span class="min-w-0"><span class="block truncate text-sm font-semibold">{{ $menu->title }}</span><span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $menu->handle }}</span></span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $menu->items_count }} links</span>
                    </button>
                @endforeach
            </div>
        </section>

        @if ($selectedMenu)
            <section aria-labelledby="navigation-editor-heading" class="min-w-0 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 id="navigation-editor-heading" class="text-lg font-semibold">{{ $selectedMenu->title }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Drag links to reorder them. Drop a link onto a top-level item to make it a submenu link.</p>
                    </div>
                    <flux:button type="button" wire:click="addItem" icon="plus">Add item</flux:button>
                </div>

                @error('menuItems')
                    <p class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200" role="alert">{{ $message }}</p>
                @enderror

                @if ($menuItems === [])
                    <div class="mt-6 rounded-2xl border border-dashed border-zinc-300 px-5 py-10 text-center dark:border-zinc-700">
                        <p class="font-medium">This menu is empty</p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Add links, pages, collections, or products to show them in your storefront.</p>
                    </div>
                @else
                    <ul class="mt-6 grid gap-3" wire:sort="reorderItems" wire:sort:group="navigation-menu-items" wire:sort:group-id="root" aria-label="{{ $selectedMenu->title }} items">
                        @foreach ($menuItems as $index => $item)
                            @php
                                $resourceGroup = ['page' => 'pages', 'collection' => 'collections', 'product' => 'products'][$item['type']] ?? null;
                                $resource = $resourceGroup && ! empty($item['resource_id']) ? $resources[$resourceGroup]->firstWhere('id', (int) $item['resource_id']) : null;
                                $target = $item['type'] === 'link' ? $item['url'] : $item['type'].': '.($resource?->title ?? 'Unavailable resource');
                            @endphp
                            <li wire:key="navigation-item-{{ $item['id'] }}" wire:sort:item="{{ $item['id'] }}" class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-950">
                                <div class="flex items-center gap-3">
                                    <button type="button" wire:sort:handle class="inline-flex size-10 shrink-0 cursor-grab items-center justify-center rounded-lg text-zinc-500 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 active:cursor-grabbing dark:hover:bg-zinc-800" aria-label="Drag {{ $item['label'] }} to reorder">⋮⋮</button>
                                    <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ $item['label'] }}</p><p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $target }}</p></div>
                                    <div wire:sort:ignore class="flex shrink-0 items-center gap-1">
                                        <button type="button" wire:click="addItem('{{ $item['id'] }}')" class="inline-flex min-h-10 items-center rounded-lg px-3 text-xs font-medium text-zinc-600 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800">Add submenu</button>
                                        <button type="button" wire:click="editItem('{{ $item['id'] }}')" class="inline-flex min-h-10 items-center rounded-lg px-3 text-xs font-medium text-zinc-600 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800">Edit</button>
                                        <button type="button" wire:click="removeItem('{{ $item['id'] }}')" wire:confirm="Remove this menu item and its submenu links?" class="inline-flex min-h-10 items-center rounded-lg px-3 text-xs font-medium text-rose-700 hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-rose-600 dark:text-rose-300 dark:hover:bg-rose-950/40">Remove</button>
                                    </div>
                                </div>

                                <ul class="ml-10 mt-3 grid gap-2 border-l border-zinc-200 pl-3 dark:border-zinc-700" wire:sort="reorderItems" wire:sort:group="navigation-menu-items" wire:sort:group-id="{{ $item['id'] }}" aria-label="{{ $item['label'] }} submenu">
                                    @foreach ($item['children'] as $childIndex => $child)
                                        @php
                                            $childResourceGroup = ['page' => 'pages', 'collection' => 'collections', 'product' => 'products'][$child['type']] ?? null;
                                            $childResource = $childResourceGroup && ! empty($child['resource_id']) ? $resources[$childResourceGroup]->firstWhere('id', (int) $child['resource_id']) : null;
                                            $childTarget = $child['type'] === 'link' ? $child['url'] : $child['type'].': '.($childResource?->title ?? 'Unavailable resource');
                                        @endphp
                                        <li wire:key="navigation-item-{{ $child['id'] }}" wire:sort:item="{{ $child['id'] }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900">
                                            <button type="button" wire:sort:handle class="inline-flex size-9 shrink-0 cursor-grab items-center justify-center rounded-lg text-zinc-500 hover:bg-white focus-visible:outline-2 focus-visible:outline-blue-600 active:cursor-grabbing dark:hover:bg-zinc-800" aria-label="Drag {{ $child['label'] }} to reorder">⋮⋮</button>
                                            <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ $child['label'] }}</p><p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $childTarget }}</p></div>
                                            <div wire:sort:ignore class="flex shrink-0 items-center gap-1">
                                                <button type="button" wire:click="editItem('{{ $child['id'] }}')" class="inline-flex min-h-9 items-center rounded-lg px-3 text-xs font-medium text-zinc-600 hover:bg-white focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800">Edit</button>
                                                <button type="button" wire:click="removeItem('{{ $child['id'] }}')" wire:confirm="Remove this submenu link?" class="inline-flex min-h-9 items-center rounded-lg px-3 text-xs font-medium text-rose-700 hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-rose-600 dark:text-rose-300 dark:hover:bg-rose-950/40">Remove</button>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-6 flex justify-end border-t border-zinc-200 pt-5 dark:border-zinc-800">
                    <flux:button type="button" variant="primary" wire:click="saveMenu" wire:loading.attr="disabled" wire:target="saveMenu">Save menu</flux:button>
                </div>
            </section>
        @endif
    </div>

    <flux:modal name="item-form" wire:model="showItemModal" class="max-w-xl">
        <form wire:submit="saveItem" class="grid gap-5">
            <div><flux:heading size="lg">{{ $editingItemId === null ? 'Add menu item' : 'Edit menu item' }}</flux:heading><flux:subheading>Choose a link target and where it appears in the menu.</flux:subheading></div>
            <flux:field>
                <flux:label for="navigation-item-label">Label</flux:label>
                <flux:input id="navigation-item-label" wire:model="itemLabel" placeholder="About Us" />
                <flux:error name="itemLabel" />
            </flux:field>
            <flux:field>
                <flux:label for="navigation-item-type">Type</flux:label>
                <flux:select id="navigation-item-type" wire:model.live="itemType">
                    <flux:select.option value="link">Custom link</flux:select.option>
                    <flux:select.option value="page">Page</flux:select.option>
                    <flux:select.option value="collection">Collection</flux:select.option>
                    <flux:select.option value="product">Product</flux:select.option>
                </flux:select>
                <flux:error name="itemType" />
            </flux:field>
            @if ($itemType === 'link')
                <flux:field>
                    <flux:label for="navigation-item-url">URL</flux:label>
                    <flux:input id="navigation-item-url" wire:model="itemUrl" placeholder="https://example.com or /pages/about" />
                    <flux:error name="itemUrl" />
                </flux:field>
            @else
                @php($resourceGroup = ['page' => 'pages', 'collection' => 'collections', 'product' => 'products'][$itemType] ?? null)
                <flux:field>
                    <flux:label for="navigation-item-resource">{{ ucfirst($itemType) }}</flux:label>
                    <flux:select id="navigation-item-resource" wire:model="itemResourceId" placeholder="Choose {{ $itemType }}">
                        @foreach ($resources[$resourceGroup] ?? [] as $resource)
                            <flux:select.option value="{{ $resource->id }}">{{ $resource->title }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="itemResourceId" />
                </flux:field>
            @endif
            <flux:field>
                <flux:label for="navigation-item-parent">Parent item</flux:label>
                <flux:select id="navigation-item-parent" wire:model="itemParentId" placeholder="Top-level item">
                    <flux:select.option value="">Top-level item</flux:select.option>
                    @foreach ($menuItems as $parentItem)
                        @if ($parentItem['id'] !== $editingItemId)
                            <flux:select.option value="{{ $parentItem['id'] }}">{{ $parentItem['label'] }}</flux:select.option>
                        @endif
                    @endforeach
                </flux:select>
                <flux:error name="itemParentId" />
            </flux:field>
            <div class="flex justify-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <flux:modal.close><flux:button type="button" variant="ghost" wire:click="$set('showItemModal', false)">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save item</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
