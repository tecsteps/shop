<div>
    <div class="mb-6">
        <flux:heading size="xl">Inventory</flux:heading>
    </div>

    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by product or SKU..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="stockFilter" class="w-40">
            <flux:select.option value="all">All stock</flux:select.option>
            <flux:select.option value="in_stock">In stock</flux:select.option>
            <flux:select.option value="low_stock">Low stock</flux:select.option>
            <flux:select.option value="out_of_stock">Out of stock</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        @if ($this->inventoryItems->isEmpty())
            <div class="py-12 text-center">
                <flux:text class="text-zinc-400">No inventory items found.</flux:text>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Product</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">SKU</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">On Hand</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Reserved</th>
                        <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Policy</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->inventoryItems as $item)
                        <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $item->product_title }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $item->sku ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <flux:input
                                    type="number"
                                    value="{{ $item->quantity_on_hand }}"
                                    wire:change="updateQuantity({{ $item->id }}, $event.target.value)"
                                    size="sm"
                                    class="w-20"
                                />
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $item->quantity_reserved }}</td>
                            <td class="px-4 py-3">
                                <flux:badge size="sm">{{ $item->policy->value }}</flux:badge>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3">{{ $this->inventoryItems->links() }}</div>
        @endif
    </div>
</div>
