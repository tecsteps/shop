<div>
    <flux:heading size="xl">{{ __('Inventory') }}</flux:heading>

    <div class="mt-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by product...') }}" class="w-64" icon="magnifying-glass" />
    </div>

    <flux:table class="mt-4">
        <flux:table.columns>
            <flux:table.column>{{ __('Product') }}</flux:table.column>
            <flux:table.column>{{ __('SKU') }}</flux:table.column>
            <flux:table.column align="end">{{ __('On Hand') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Reserved') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Available') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($variants as $variant)
                <flux:table.row :key="$variant->id">
                    <flux:table.cell variant="strong">{{ $variant->product?->title ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $variant->sku ?? '-' }}</flux:table.cell>
                    <flux:table.cell align="end">{{ $variant->inventoryItem?->quantity_on_hand ?? 0 }}</flux:table.cell>
                    <flux:table.cell align="end">{{ $variant->inventoryItem?->quantity_reserved ?? 0 }}</flux:table.cell>
                    <flux:table.cell align="end">
                        {{ ($variant->inventoryItem?->quantity_on_hand ?? 0) - ($variant->inventoryItem?->quantity_reserved ?? 0) }}
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">
                        <flux:text class="text-center">{{ __('No inventory items found.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $variants->links() }}
    </div>
</div>
