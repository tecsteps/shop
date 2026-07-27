<div class="space-y-4">
    <flux:heading size="xl">Inventory</flux:heading>

    <div class="flex flex-wrap items-center gap-3">
        <flux:input icon="magnifying-glass" wire:model.live.debounce.300ms="search" placeholder="Search by product or SKU" class="w-full sm:w-72" aria-label="Search inventory" />

        <flux:select wire:model.live="stockFilter" class="w-44" aria-label="Stock filter">
            <flux:select.option value="all">All stock</flux:select.option>
            <flux:select.option value="in_stock">In stock</flux:select.option>
            <flux:select.option value="low_stock">Low stock</flux:select.option>
            <flux:select.option value="out_of_stock">Out of stock</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full min-w-[720px] text-left text-sm" wire:loading.class="opacity-50">
            <thead>
                <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                    <th class="px-4 py-3 font-medium">Product</th>
                    <th class="px-4 py-3 font-medium">Variant</th>
                    <th class="px-4 py-3 font-medium">SKU</th>
                    <th class="px-4 py-3 font-medium">On hand</th>
                    <th class="px-4 py-3 font-medium">Reserved</th>
                    <th class="px-4 py-3 font-medium">Available</th>
                    <th class="px-4 py-3 font-medium">Policy</th>
                    <th class="px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($items as $item)
                    <tr wire:key="inventory-{{ $item->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $item->variant->product->title }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $item->variant->title() }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $item->variant->sku ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                <button type="button" wire:click="adjustQuantity({{ $item->id }}, -1)" class="rounded border border-zinc-200 px-1.5 py-0.5 text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="Decrease quantity">−</button>
                                <input type="number"
                                       value="{{ $item->quantity_on_hand }}"
                                       wire:change="setQuantity({{ $item->id }}, $event.target.value)"
                                       min="0"
                                       aria-label="Quantity on hand"
                                       class="w-20 rounded border border-zinc-200 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800" />
                                <button type="button" wire:click="adjustQuantity({{ $item->id }}, 1)" class="rounded border border-zinc-200 px-1.5 py-0.5 text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="Increase quantity">+</button>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $item->quantity_reserved }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ $item->available() <= 0 ? 'font-semibold text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-300' }}">
                                {{ $item->available() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <button type="button" wire:click="togglePolicy({{ $item->id }})" aria-label="Toggle inventory policy">
                                <flux:badge size="sm" :color="$item->policy === \App\Enums\InventoryPolicy::Continue ? 'blue' : 'zinc'">
                                    {{ $item->policy->value }}
                                </flux:badge>
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <flux:button size="sm" variant="ghost" :href="route('admin.products.edit', $item->variant->product)" wire:navigate>Edit product</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            No inventory items match your filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $items->links() }}
</div>
