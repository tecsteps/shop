<div class="space-y-6">
    <div>
        <flux:heading size="xl">Inventory</flux:heading>
        <flux:text>Track available stock and oversell policy by variant.</flux:text>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-800">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products" icon="magnifying-glass" />
        </div>

        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($items as $item)
                <div wire:key="inventory-item-{{ $item->id }}" class="grid gap-4 p-5 lg:grid-cols-[1fr_8rem_12rem_auto] lg:items-end">
                    <div>
                        <div class="font-medium">{{ $item->variant?->product?->title }}</div>
                        <div class="text-sm text-zinc-500">{{ $item->variant?->sku ?: 'No SKU' }} · Reserved {{ $item->quantity_reserved }}</div>
                    </div>
                    <flux:input wire:model="quantities.{{ $item->id }}" type="number" min="0" label="On hand" />
                    <flux:select wire:model="policies.{{ $item->id }}" label="Policy">
                        @foreach ($policyOptions as $policy)
                            <option value="{{ $policy->value }}">{{ ucfirst($policy->value) }}</option>
                        @endforeach
                    </flux:select>
                    <flux:button wire:click="saveItem({{ $item->id }})">Save</flux:button>
                </div>
            @empty
                <div class="p-10 text-sm text-zinc-500">No inventory items match the current filters.</div>
            @endforelse
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">{{ $items->links() }}</div>
    </div>
</div>
