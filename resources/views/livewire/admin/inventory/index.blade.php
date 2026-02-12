<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Inventory</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Inventory</flux:heading>

    {{-- Search --}}
    <div class="mb-6 max-w-md">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by SKU or product title..." icon="magnifying-glass" />
    </div>

    {{-- Table --}}
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800">
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Product</th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">SKU</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">On Hand</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">Reserved</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">Available</th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="inv-{{ $item->id }}">
                        <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                            {{ $item->product_title }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 font-mono text-xs">
                            {{ $item->sku ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if (isset($editingQuantities[$item->id]))
                                <flux:input
                                    type="number"
                                    wire:model="editingQuantities.{{ $item->id }}"
                                    class="w-24 text-right ml-auto"
                                    min="0"
                                />
                            @else
                                <span class="text-zinc-900 dark:text-zinc-100">{{ $item->quantity_on_hand }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">
                            {{ $item->quantity_reserved }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <flux:badge size="sm" :color="$item->available > 0 ? 'green' : 'red'">
                                {{ $item->available }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if (isset($editingQuantities[$item->id]))
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button wire:click="updateQuantity({{ $item->id }})" size="sm" variant="primary" icon="check" />
                                    <flux:button wire:click="$unset('editingQuantities.{{ $item->id }}')" size="sm" variant="ghost" icon="x-mark" />
                                </div>
                            @else
                                <flux:button wire:click="$set('editingQuantities.{{ $item->id }}', {{ $item->quantity_on_hand }})" size="sm" variant="ghost" icon="pencil-square" />
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            No inventory items found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>
</div>
