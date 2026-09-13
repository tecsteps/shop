<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Inventory</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">Inventory</flux:heading>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search by product or SKU" aria-label="Search inventory" class="max-w-xs flex-1" />
        <flux:select wire:model.live="stockFilter" aria-label="Filter by stock level" class="w-44">
            <flux:select.option value="all">All stock</flux:select.option>
            <flux:select.option value="in_stock">In stock</flux:select.option>
            <flux:select.option value="low_stock">Low stock</flux:select.option>
            <flux:select.option value="out_of_stock">Out of stock</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" wire:loading.class="opacity-50">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                    <th class="px-4 py-3 font-medium">Product</th>
                    <th class="px-4 py-3 font-medium">Variant</th>
                    <th class="px-4 py-3 font-medium">SKU</th>
                    <th class="px-4 py-3 font-medium">On Hand</th>
                    <th class="px-4 py-3 font-medium">Reserved</th>
                    <th class="px-4 py-3 font-medium">Policy</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($inventoryItems as $item)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="inventory-{{ $item->getKey() }}">
                        <td class="px-4 py-3">{{ $item->variant?->product?->title ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $item->variant?->optionValues->pluck('value')->implode(' / ') ?: 'Default' }}</td>
                        <td class="px-4 py-3">{{ $item->variant?->sku ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <flux:input
                                type="number"
                                min="0"
                                value="{{ $item->quantity_on_hand }}"
                                wire:change="updateQuantity({{ $item->getKey() }}, $event.target.value)"
                                aria-label="On hand quantity"
                                class="w-24"
                            />
                        </td>
                        <td class="px-4 py-3">{{ $item->quantity_reserved }}</td>
                        <td class="px-4 py-3">
                            <flux:select value="{{ $item->policy->value }}" wire:change="updatePolicy({{ $item->getKey() }}, $event.target.value)" aria-label="Inventory policy" class="w-32">
                                <flux:select.option value="deny">Deny</flux:select.option>
                                <flux:select.option value="continue">Continue</flux:select.option>
                            </flux:select>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center"><flux:text variant="subtle">No inventory items found.</flux:text></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $inventoryItems->links() }}
    </div>
</div>
