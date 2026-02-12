<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Navigation</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Navigation</flux:heading>
        <flux:button wire:click="openMenuModal" variant="primary" icon="plus">Add menu</flux:button>
    </div>

    <div class="max-w-3xl space-y-6">
        @forelse ($menus as $menu)
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" wire:key="menu-{{ $menu->id }}">
                <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <div>
                        <flux:heading size="base">{{ $menu->title }}</flux:heading>
                        <flux:text class="text-xs text-zinc-500">{{ $menu->handle }}</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:button wire:click="openItemModal({{ $menu->id }})" size="sm" variant="ghost" icon="plus">Add item</flux:button>
                        <flux:button wire:click="openMenuModal({{ $menu->id }})" size="sm" variant="ghost" icon="pencil-square" />
                        <flux:button wire:click="deleteMenu({{ $menu->id }})" wire:confirm="Delete this menu and all its items?" size="sm" variant="ghost" icon="trash" />
                    </div>
                </div>

                @if ($menu->items->count() > 0)
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($menu->items as $item)
                            <div class="flex items-center justify-between px-4 py-3" wire:key="item-{{ $item->id }}">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-zinc-400 font-mono w-6">{{ $item->position }}</span>
                                    <div>
                                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item->label }}</span>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <flux:badge size="sm" color="zinc">{{ ucfirst($item->type->value) }}</flux:badge>
                                            @if ($item->url)
                                                <span class="text-xs text-zinc-500">{{ $item->url }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <flux:button wire:click="openItemModal({{ $menu->id }}, {{ $item->id }})" size="sm" variant="ghost" icon="pencil-square" />
                                    <flux:button wire:click="deleteItem({{ $item->id }})" wire:confirm="Delete this navigation item?" size="sm" variant="ghost" icon="trash" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4">
                        <flux:text class="text-zinc-500">No items in this menu.</flux:text>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:icon name="bars-3" variant="outline" class="mx-auto size-12 text-zinc-400" />
                <flux:heading size="md" class="mt-4">No navigation menus</flux:heading>
                <flux:text class="mt-1 text-zinc-500">Create a menu to organize your storefront navigation.</flux:text>
                <flux:button wire:click="openMenuModal" variant="primary" class="mt-4">Add menu</flux:button>
            </div>
        @endforelse
    </div>

    {{-- Menu Modal --}}
    @if ($showMenuModal)
        <flux:modal name="menu-form" :show="true" class="max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">{{ $editingMenuId ? 'Edit' : 'Add' }} menu</flux:heading>
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="menuTitle" placeholder="e.g. Main menu" />
                    <flux:error name="menuTitle" />
                </flux:field>
                <flux:field>
                    <flux:label>Handle</flux:label>
                    <flux:input wire:model="menuHandle" placeholder="e.g. main-menu" />
                    <flux:error name="menuHandle" />
                </flux:field>
                <div class="flex justify-end gap-2">
                    <flux:button wire:click="$set('showMenuModal', false)" variant="ghost">Cancel</flux:button>
                    <flux:button wire:click="saveMenu" variant="primary">Save menu</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Item Modal --}}
    @if ($showItemModal)
        <flux:modal name="item-form" :show="true" class="max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">{{ $editingItemId ? 'Edit' : 'Add' }} navigation item</flux:heading>
                <flux:field>
                    <flux:label>Label</flux:label>
                    <flux:input wire:model="itemLabel" placeholder="e.g. About us" />
                    <flux:error name="itemLabel" />
                </flux:field>
                <flux:field>
                    <flux:label>Type</flux:label>
                    <flux:select wire:model="itemType">
                        @foreach ($itemTypes as $it)
                            <flux:select.option value="{{ $it->value }}">{{ ucfirst($it->value) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>URL</flux:label>
                    <flux:input wire:model="itemUrl" placeholder="/pages/about-us" />
                    <flux:description>Relative or absolute URL.</flux:description>
                    <flux:error name="itemUrl" />
                </flux:field>
                <flux:field>
                    <flux:label>Position</flux:label>
                    <flux:input wire:model="itemPosition" type="number" min="0" />
                </flux:field>
                <div class="flex justify-end gap-2">
                    <flux:button wire:click="$set('showItemModal', false)" variant="ghost">Cancel</flux:button>
                    <flux:button wire:click="saveItem" variant="primary">Save item</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
