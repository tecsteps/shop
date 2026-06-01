<div>
    <x-admin.breadcrumbs :items="[['label' => __('Inventory')]]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ __('Inventory') }}</flux:heading>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by product or SKU')" class="max-w-xs" />
        <flux:select wire:model.live="stockFilter" class="w-44">
            <flux:select.option value="all">{{ __('All stock') }}</flux:select.option>
            <flux:select.option value="in_stock">{{ __('In stock') }}</flux:select.option>
            <flux:select.option value="low_stock">{{ __('Low stock') }}</flux:select.option>
            <flux:select.option value="out_of_stock">{{ __('Out of stock') }}</flux:select.option>
        </flux:select>
    </div>

    <flux:table :paginate="$this->inventoryItems">
        <flux:table.columns>
            <flux:table.column>{{ __('Product') }}</flux:table.column>
            <flux:table.column>{{ __('Variant') }}</flux:table.column>
            <flux:table.column>{{ __('SKU') }}</flux:table.column>
            <flux:table.column>{{ __('On hand') }}</flux:table.column>
            <flux:table.column>{{ __('Reserved') }}</flux:table.column>
            <flux:table.column>{{ __('Policy') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->inventoryItems as $item)
                <flux:table.row :key="'inv-'.$item->id">
                    <flux:table.cell variant="strong">{{ $item->variant->product->title }}</flux:table.cell>
                    <flux:table.cell>{{ $item->variant->optionValues->pluck('value')->implode(' / ') ?: __('Default') }}</flux:table.cell>
                    <flux:table.cell>{{ $item->variant->sku ?: '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:input
                            type="number"
                            value="{{ $item->quantity_on_hand }}"
                            wire:change="updateOnHand({{ $item->id }}, $event.target.value)"
                            class="w-24"
                            size="sm"
                            data-test="on-hand-{{ $item->id }}"
                        />
                    </flux:table.cell>
                    <flux:table.cell>{{ $item->quantity_reserved }}</flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" color="zinc">{{ $item->policy->value }}</flux:badge></flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center">{{ __('No inventory items found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
