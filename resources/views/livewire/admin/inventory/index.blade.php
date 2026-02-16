<div>
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
            <thead class="bg-gray-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Product / Variant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">SKU</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">On Hand</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Reserved</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Available</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Adjust</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($items as $item)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            {{ $item->variant?->product?->title ?? 'Unknown' }}
                            @if($item->variant?->sku) <span class="text-gray-500 dark:text-gray-400">- {{ $item->variant->sku }}</span> @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $item->variant?->sku ?? '-' }}</td>
                        <td class="px-6 py-4 text-center text-sm text-gray-900 dark:text-white">{{ $item->quantity_on_hand }}</td>
                        <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">{{ $item->quantity_reserved }}</td>
                        <td class="px-6 py-4 text-center text-sm font-medium {{ $item->quantity_available < 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">{{ $item->quantity_available }}</td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <flux:button wire:click="adjustQuantity({{ $item->id }}, -1)" variant="ghost" size="sm">-</flux:button>
                                <flux:button wire:click="adjustQuantity({{ $item->id }}, 1)" variant="ghost" size="sm">+</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No inventory items.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
</div>
