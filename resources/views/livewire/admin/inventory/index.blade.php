<div>
    {{-- Header --}}
    <div class="mb-6">
        <flux:heading size="xl">Inventory</flux:heading>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Search by product or SKU..."
                clearable
            />
        </div>
        <flux:select wire:model.live="stockFilter" class="w-auto sm:w-44">
            <flux:select.option value="all">All stock</flux:select.option>
            <flux:select.option value="in_stock">In stock</flux:select.option>
            <flux:select.option value="low_stock">Low stock (< 5)</flux:select.option>
            <flux:select.option value="out_of_stock">Out of stock</flux:select.option>
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
        @if ($this->inventoryItems->count() > 0)
            <div class="overflow-x-auto" wire:loading.class="opacity-50">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Product</th>
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Variant</th>
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">SKU</th>
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">On Hand</th>
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Reserved</th>
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Available</th>
                            <th class="text-left px-6 py-3 font-medium text-zinc-500 dark:text-zinc-400">Policy</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($this->inventoryItems as $item)
                            <tr wire:key="inventory-{{ $item->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-6 py-3 font-medium text-zinc-900 dark:text-white">
                                    {{ $item->variant?->product?->title ?? '-' }}
                                </td>
                                <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $item->variant?->title ?? '-' }}
                                </td>
                                <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400 font-mono text-xs">
                                    {{ $item->variant?->sku ?? $item->sku ?? '-' }}
                                </td>
                                <td class="px-6 py-3">
                                    <input
                                        type="number"
                                        value="{{ $item->quantity_on_hand }}"
                                        wire:change="updateQuantity({{ $item->id }}, $event.target.value)"
                                        min="0"
                                        class="w-20 px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white"
                                    >
                                </td>
                                <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $item->quantity_reserved }}
                                </td>
                                <td class="px-6 py-3">
                                    @php $available = $item->quantityAvailable(); @endphp
                                    <span class="{{ $available <= 0 ? 'text-red-600 dark:text-red-400 font-bold' : ($available < 5 ? 'text-yellow-600 dark:text-yellow-400' : 'text-zinc-900 dark:text-white') }}">
                                        {{ $available }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <flux:badge size="sm" :color="$item->policy === \App\Enums\InventoryPolicy::Deny ? 'red' : 'green'">
                                        {{ $item->policy?->value ?? 'deny' }}
                                    </flux:badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->inventoryItems->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <flux:icon name="archive-box" class="size-12 mx-auto text-zinc-300 dark:text-zinc-600" />
                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">No inventory items found.</flux:text>
            </div>
        @endif
    </div>
</div>
