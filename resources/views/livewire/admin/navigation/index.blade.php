<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Navigation') }}</flux:heading>
    </div>

    <div class="grid grid-cols-12 gap-6">
        {{-- Menu list --}}
        <div class="col-span-12 lg:col-span-4">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                <h3 class="text-sm font-medium text-zinc-500 uppercase mb-3">{{ __('Menus') }}</h3>

                <div class="space-y-1 mb-4">
                    @foreach($this->menus as $menu)
                        <div class="flex items-center justify-between px-3 py-2 rounded text-sm transition cursor-pointer {{ ($this->selectedMenu?->id === $menu->id) ? 'bg-accent text-white' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                             wire:click="selectMenu({{ $menu->id }})">
                            <span>{{ $menu->title }}</span>
                            <flux:button size="xs" variant="ghost" wire:click.stop="deleteMenu({{ $menu->id }})" wire:confirm="{{ __('Delete this menu and all its items?') }}" icon="trash" class="{{ ($this->selectedMenu?->id === $menu->id) ? '!text-white' : '' }}" />
                        </div>
                    @endforeach
                </div>

                <flux:separator class="my-3" />

                <form wire:submit="createMenu" class="flex gap-2">
                    <flux:input wire:model="newMenuTitle" placeholder="{{ __('Menu name') }}" size="sm" class="flex-1" />
                    <flux:button type="submit" size="sm" variant="primary">{{ __('Add') }}</flux:button>
                </form>
            </div>
        </div>

        {{-- Menu items --}}
        <div class="col-span-12 lg:col-span-8">
            @if($this->selectedMenu)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h3 class="font-medium text-zinc-900 dark:text-zinc-100">{{ $this->selectedMenu->title }} - {{ __('Items') }}</h3>
                    </div>

                    @if($this->selectedMenu->items->count() > 0)
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="p-3 text-left font-medium text-zinc-500">{{ __('Label') }}</th>
                                    <th class="p-3 text-left font-medium text-zinc-500">{{ __('Type') }}</th>
                                    <th class="p-3 text-left font-medium text-zinc-500">{{ __('URL') }}</th>
                                    <th class="p-3 text-right font-medium text-zinc-500">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->selectedMenu->items->sortBy('position') as $item)
                                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                        @if($editingItemId === $item->id)
                                            <td class="p-3">
                                                <flux:input wire:model="editItemLabel" size="sm" />
                                            </td>
                                            <td class="p-3">
                                                <flux:select wire:model="editItemType" size="sm">
                                                    @foreach(\App\Enums\NavigationItemType::cases() as $type)
                                                        <flux:select.option value="{{ $type->value }}">{{ ucfirst($type->value) }}</flux:select.option>
                                                    @endforeach
                                                </flux:select>
                                            </td>
                                            <td class="p-3">
                                                <flux:input wire:model="editItemUrl" size="sm" />
                                            </td>
                                            <td class="p-3 text-right">
                                                <div class="flex justify-end gap-1">
                                                    <flux:button size="xs" variant="primary" wire:click="updateItem">{{ __('Save') }}</flux:button>
                                                    <flux:button size="xs" variant="ghost" wire:click="$set('editingItemId', null)">{{ __('Cancel') }}</flux:button>
                                                </div>
                                            </td>
                                        @else
                                            <td class="p-3">{{ $item->label }}</td>
                                            <td class="p-3">
                                                <flux:badge size="sm">{{ ucfirst($item->type->value) }}</flux:badge>
                                            </td>
                                            <td class="p-3 text-zinc-500">{{ $item->url }}</td>
                                            <td class="p-3 text-right">
                                                <div class="flex justify-end gap-1">
                                                    <flux:button size="xs" variant="ghost" wire:click="moveItemUp({{ $item->id }})" icon="chevron-up" />
                                                    <flux:button size="xs" variant="ghost" wire:click="moveItemDown({{ $item->id }})" icon="chevron-down" />
                                                    <flux:button size="xs" variant="ghost" wire:click="editItem({{ $item->id }})" icon="pencil" />
                                                    <flux:button size="xs" variant="ghost" wire:click="deleteItem({{ $item->id }})" wire:confirm="{{ __('Delete this item?') }}" icon="trash" />
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="p-8 text-center">
                            <flux:text class="text-zinc-500">{{ __('No items in this menu.') }}</flux:text>
                        </div>
                    @endif

                    {{-- Add item form --}}
                    <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                        <form wire:submit="addItem" class="flex items-end gap-3">
                            <div class="flex-1">
                                <flux:input wire:model="newItemLabel" label="{{ __('Label') }}" size="sm" />
                            </div>
                            <div class="w-32">
                                <flux:select wire:model="newItemType" label="{{ __('Type') }}" size="sm">
                                    @foreach(\App\Enums\NavigationItemType::cases() as $type)
                                        <flux:select.option value="{{ $type->value }}">{{ ucfirst($type->value) }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                            <div class="flex-1">
                                <flux:input wire:model="newItemUrl" label="{{ __('URL') }}" size="sm" />
                            </div>
                            <flux:button type="submit" size="sm" variant="primary">{{ __('Add item') }}</flux:button>
                        </form>
                    </div>
                </div>
            @else
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-12 text-center">
                    <flux:heading size="lg">{{ __('No menus yet') }}</flux:heading>
                    <flux:text class="mt-2 text-zinc-500">{{ __('Create a menu to manage your navigation.') }}</flux:text>
                </div>
            @endif
        </div>
    </div>
</div>
