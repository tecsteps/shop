<div>
    @include('livewire.admin.components.page-heading', [
        'title' => 'Inventory',
        'description' => 'Track on-hand stock and backorder settings across your product variants.',
    ])

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-label="Inventory list">
        <div class="flex flex-col gap-3 border-b border-zinc-200 p-4 sm:flex-row sm:items-center dark:border-zinc-800">
            <label for="inventory-search" class="sr-only">Search inventory by product or SKU</label>
            <div class="relative min-w-0 flex-1">
                <flux:icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
                <input
                    id="inventory-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by product or SKU"
                    class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white pl-10 pr-3 text-sm outline-none placeholder:text-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950"
                />
            </div>

            <label for="inventory-stock-filter" class="sr-only">Filter inventory by stock level</label>
            <select
                id="inventory-stock-filter"
                wire:model.live="stockFilter"
                class="min-h-11 rounded-xl border border-zinc-300 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950"
            >
                <option value="all">All stock levels</option>
                <option value="in_stock">In stock</option>
                <option value="low_stock">Low stock (1–5 available)</option>
                <option value="out_of_stock">Out of stock</option>
            </select>
        </div>

        <div wire:loading.class="opacity-50" wire:target="search,stockFilter" class="overflow-x-auto transition-opacity">
            @if ($inventoryItems->count())
                <table class="w-full min-w-[58rem] text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-950/50 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="px-5 py-3">Product</th>
                            <th scope="col" class="px-4 py-3">Variant</th>
                            <th scope="col" class="px-4 py-3">SKU</th>
                            <th scope="col" class="px-4 py-3">On hand</th>
                            <th scope="col" class="px-4 py-3">Reserved</th>
                            <th scope="col" class="px-4 py-3">Policy</th>
                            @if ($canManageInventory)<th scope="col" class="px-5 py-3 text-right"><span class="sr-only">Actions</span></th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($inventoryItems as $inventoryItem)
                            @php($variantTitle = $inventoryItem->variant->title)
                            <tr wire:key="admin-inventory-{{ $inventoryItem->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <th scope="row" class="max-w-64 px-5 py-4 font-medium text-zinc-950 dark:text-zinc-100">
                                    <span class="block truncate">{{ $inventoryItem->variant->product->title }}</span>
                                </th>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $variantTitle }}</td>
                                <td class="px-4 py-4 font-mono text-xs text-zinc-600 dark:text-zinc-300">{{ $inventoryItem->variant->sku ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($canManageInventory)
                                        <label for="inventory-quantity-{{ $inventoryItem->id }}" class="sr-only">On-hand quantity for {{ $inventoryItem->variant->product->title }} {{ $variantTitle }}</label>
                                        <input id="inventory-quantity-{{ $inventoryItem->id }}" type="number" min="0" step="1" wire:model="quantities.{{ $inventoryItem->id }}" class="min-h-10 w-24 rounded-lg border border-zinc-300 bg-white px-2.5 text-sm tabular-nums outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950" />
                                        @error('quantities.'.$inventoryItem->id)
                                            <span class="mt-1 block max-w-32 text-xs text-rose-700 dark:text-rose-300">{{ $message }}</span>
                                        @enderror
                                    @else
                                        <span class="tabular-nums">{{ number_format($inventoryItem->quantity_on_hand) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 tabular-nums text-zinc-600 dark:text-zinc-300">{{ number_format($inventoryItem->quantity_reserved) }}</td>
                                <td class="px-4 py-3">
                                    <div class="grid justify-items-start gap-2">
                                        <flux:badge :color="$inventoryItem->policy === 'continue' ? 'blue' : 'zinc'">{{ $inventoryItem->policy }}</flux:badge>
                                        @if ($canManageInventory)
                                            <label for="inventory-policy-{{ $inventoryItem->id }}" class="sr-only">Inventory policy for {{ $inventoryItem->variant->product->title }} {{ $variantTitle }}</label>
                                            <select id="inventory-policy-{{ $inventoryItem->id }}" wire:model="policies.{{ $inventoryItem->id }}" class="min-h-9 rounded-lg border border-zinc-300 bg-white px-2 text-xs focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                                                <option value="deny">Deny when out of stock</option>
                                                <option value="continue">Allow backorders</option>
                                            </select>
                                            @error('policies.'.$inventoryItem->id)
                                                <span class="text-xs text-rose-700 dark:text-rose-300">{{ $message }}</span>
                                            @enderror
                                        @endif
                                    </div>
                                </td>
                                @if ($canManageInventory)<td class="px-5 py-3 text-right">
                                    <button
                                        type="button"
                                        wire:click="saveInventoryItem({{ $inventoryItem->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="saveInventoryItem({{ $inventoryItem->id }})"
                                        class="inline-flex min-h-10 items-center justify-center rounded-lg bg-blue-600 px-3 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:cursor-wait disabled:opacity-60"
                                    >
                                        Save
                                    </button>
                                </td>@endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-6 py-16 text-center">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800"><flux:icon name="cube" class="size-7" /></span>
                    <h2 class="mt-4 text-lg font-semibold">{{ $search || $stockFilter !== 'all' ? 'No inventory matches these filters' : 'No inventory to manage yet' }}</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $search || $stockFilter !== 'all' ? 'Try a different product, SKU, or stock level.' : 'Inventory appears here when products have variants.' }}</p>
                </div>
            @endif
        </div>

        @if ($inventoryItems->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">{{ $inventoryItems->links() }}</div>
        @endif
    </section>
</div>
