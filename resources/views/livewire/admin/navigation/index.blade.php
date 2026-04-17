<div class="flex flex-col gap-6">
    <flux:heading size="xl">Navigation</flux:heading>
    @if ($menus->isEmpty())
        <flux:callout>No navigation menus yet. Seed demo data to create the Main menu.</flux:callout>
    @else
        <flux:select wire:model.live="menuId" class="w-fit">
            @foreach ($menus as $menu)
                <flux:select.option value="{{ $menu->id }}">{{ $menu->title }}</flux:select.option>
            @endforeach
        </flux:select>

        @php($activeMenu = $menus->firstWhere('id', $menuId))
        @if ($activeMenu)
            <div class="rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
                <flux:heading size="lg" class="mb-4">Items in {{ $activeMenu->title }}</flux:heading>
                @if ($activeMenu->items->isEmpty())
                    <div class="text-sm text-zinc-500">No items yet.</div>
                @else
                    <div class="flex flex-col divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($activeMenu->items as $item)
                            <div class="flex items-center justify-between py-2 text-sm">
                                <div><span class="font-medium">{{ $item->label }}</span> <span class="text-zinc-500">→ {{ $item->url }}</span></div>
                                <flux:button size="xs" variant="ghost" wire:click="deleteItem({{ $item->id }})">Remove</flux:button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form wire:submit="addItem" class="mt-4 flex items-end gap-2">
                    <flux:input size="sm" label="Label" wire:model="itemLabel" />
                    <flux:input size="sm" label="URL" wire:model="itemUrl" placeholder="/shop" />
                    <flux:button type="submit" size="sm" variant="primary">Add</flux:button>
                </form>
            </div>
        @endif
    @endif
</div>
