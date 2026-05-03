<div class="space-y-6">
    <div>
        <flux:heading size="xl">Navigation</flux:heading>
        <flux:text>Menus and storefront links.</flux:text>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-5 dark:border-zinc-800">
                <flux:heading size="lg">Menus</flux:heading>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($menus as $menu)
                    <div wire:key="admin-menu-{{ $menu->id }}" class="p-5">
                        <div class="font-medium">{{ $menu->title }}</div>
                        <div class="text-sm text-zinc-500">{{ $menu->handle }}</div>

                        <div class="mt-4 space-y-2">
                            @foreach ($menu->items as $item)
                                <div wire:key="admin-menu-item-{{ $item->id }}" class="flex items-center justify-between gap-4 rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-800">
                                    <span>{{ $item->label }} · {{ $item->url }}</span>
                                    <flux:button size="sm" wire:click="deleteItem({{ $item->id }})">Delete</flux:button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <form wire:submit="addItem" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="lg">Add item</flux:heading>
            <div class="mt-4 grid gap-4">
                <flux:select wire:model="menuId" label="Menu">
                    @foreach ($menus as $menu)
                        <option value="{{ $menu->id }}">{{ $menu->title }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="type" label="Type">
                    @foreach ($types as $typeOption)
                        <option value="{{ $typeOption->value }}">{{ ucfirst($typeOption->value) }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="label" label="Label" />
                <flux:input wire:model="url" label="URL" />
                <flux:button type="submit">Add item</flux:button>
            </div>
        </form>
    </div>
</div>
