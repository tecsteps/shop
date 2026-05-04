<section class="space-y-6">
    <div>
        <flux:heading size="xl">Inventory</flux:heading>
        <flux:text class="mt-1">Review stock levels by product variant.</flux:text>
    </div>

    <div class="grid gap-3 md:grid-cols-[1fr_180px]">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search by product or SKU..." aria-label="Search inventory" />

        <flux:select wire:model.live="stockFilter" aria-label="Stock filter">
            <flux:select.option value="all">All stock</flux:select.option>
            <flux:select.option value="low">Low stock</flux:select.option>
            <flux:select.option value="out">Out of stock</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">On hand</th>
                        <th class="px-4 py-3">Reserved</th>
                        <th class="px-4 py-3">Available</th>
                        <th class="px-4 py-3">Policy</th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50" class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($items as $item)
                        <tr wire:key="inventory-row-{{ $item->getKey() }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.products.edit', $item->variant->product) }}" class="font-medium text-zinc-950 hover:underline dark:text-white" wire:navigate>
                                    {{ $item->variant->product->title }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $item->variant->sku ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $item->quantity_on_hand }}</td>
                            <td class="px-4 py-3">{{ $item->quantity_reserved }}</td>
                            <td class="px-4 py-3">{{ $item->availableQuantity() }}</td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$item->policy->value === 'continue' ? 'blue' : 'zinc'">{{ Str::title($item->policy->value) }}</flux:badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center text-zinc-500">No inventory items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $items->links() }}
</section>
