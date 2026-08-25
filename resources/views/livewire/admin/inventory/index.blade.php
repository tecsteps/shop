<div>
    <flux:heading size="xl">Inventory</flux:heading>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            placeholder="Search by product or SKU..."
            class="sm:max-w-sm"
        />

        <flux:select wire:model.live="stockFilter" class="sm:w-44">
            <option value="all">All stock</option>
            <option value="in_stock">In stock</option>
            <option value="low_stock">Low stock</option>
            <option value="out_of_stock">Out of stock</option>
        </flux:select>
    </div>

    <div wire:loading.delay.class="opacity-50" class="mt-4">
        <flux:card class="overflow-hidden">
            <flux:table :paginate="$this->inventoryItems">
                <flux:table.columns>
                    <flux:table.column>Product</flux:table.column>
                    <flux:table.column>Variant</flux:table.column>
                    <flux:table.column>SKU</flux:table.column>
                    <flux:table.column>On Hand</flux:table.column>
                    <flux:table.column>Reserved</flux:table.column>
                    <flux:table.column>Policy</flux:table.column>
                    <flux:table.column class="text-end">Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->inventoryItems as $item)
                        <flux:table.row :key="$item->id">
                            <flux:table.cell variant="strong">
                                {{ $item->variant?->product?->title ?? '—' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $optionValues = $item->variant?->optionValues ?? collect();
                                    $variantLabel = $optionValues->pluck('value')->join(' / ');
                                @endphp
                                {{ $variantLabel !== '' ? $variantLabel : 'Default' }}
                            </flux:table.cell>
                            <flux:table.cell>{{ $item->variant?->sku ?: '—' }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($editingId === $item->id)
                                    <div class="flex items-center gap-2">
                                        <flux:input
                                            wire:model="editingQuantity"
                                            type="number"
                                            min="0"
                                            class="w-24"
                                            wire:keydown.enter="saveQuantity"
                                        />
                                    </div>
                                @else
                                    <span class="font-medium text-zinc-800 dark:text-white">{{ $item->quantity_on_hand }}</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $item->quantity_reserved }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$item->policy === 'deny' ? 'red' : 'blue'" size="sm">
                                    {{ $item->policy }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-end">
                                @if ($editingId === $item->id)
                                    <div class="flex justify-end gap-1">
                                        <flux:button variant="subtle" size="sm" icon="check" wire:click="saveQuantity" aria-label="Save quantity" />
                                        <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="cancelEdit" aria-label="Cancel" />
                                    </div>
                                @else
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="pencil-square"
                                        wire:click="startEdit({{ $item->id }}, {{ $item->quantity_on_hand }})"
                                        aria-label="Adjust quantity"
                                    />
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" align="center" class="py-10 text-zinc-400">
                                No inventory items match your filters.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>
