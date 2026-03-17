<div>
    <div class="mb-6">
        <flux:heading size="xl">Inventory</flux:heading>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-4">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by product or SKU..." icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="stockFilter" class="w-40">
            <option value="all">All</option>
            <option value="in_stock">In stock</option>
            <option value="low_stock">Low stock</option>
            <option value="out_of_stock">Out of stock</option>
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <table class="w-full text-left text-sm" wire:loading.class="opacity-50" wire:target="search,stockFilter">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Product</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Variant</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">SKU</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">On Hand</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Reserved</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Policy</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($inventoryItems as $item)
                    <tr class="border-b border-gray-100 dark:border-gray-800" wire:key="inv-{{ $item->id }}">
                        <td class="px-4 py-3">{{ $item->product_title }}</td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ collect([$item->option1, $item->option2, $item->option3])->filter()->implode(' / ') ?: 'Default' }}
                        </td>
                        <td class="px-4 py-3">{{ $item->sku ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <flux:input
                                type="number"
                                value="{{ $item->quantity_on_hand }}"
                                wire:change="updateQuantity({{ $item->id }}, $event.target.value)"
                                size="sm"
                                class="w-20"
                                min="0"
                            />
                        </td>
                        <td class="px-4 py-3">{{ $item->quantity_reserved }}</td>
                        <td class="px-4 py-3">
                            <flux:badge :color="$item->out_of_stock_policy === 'continue' ? 'green' : 'zinc'" size="sm">
                                {{ $item->out_of_stock_policy ?? 'deny' }}
                            </flux:badge>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-500">No inventory items found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $inventoryItems->links() }}</div>
</div>
