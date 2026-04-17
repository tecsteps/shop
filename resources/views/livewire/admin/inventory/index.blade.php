<div class="flex flex-col gap-6">
    <flux:heading size="xl">Inventory</flux:heading>
    @if (session('success'))
        <flux:callout variant="success" heading="{{ session('success') }}"></flux:callout>
    @endif
    <div class="rounded-xl bg-white ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
        @if ($items->isEmpty())
            <div class="p-10 text-center text-zinc-500">No inventory records yet.</div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Product</flux:table.column>
                    <flux:table.column>SKU</flux:table.column>
                    <flux:table.column>On hand</flux:table.column>
                    <flux:table.column>Reserved</flux:table.column>
                    <flux:table.column>Available</flux:table.column>
                    <flux:table.column>Update</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($items as $item)
                        <flux:table.row>
                            <flux:table.cell>{{ $item->variant?->product?->title }}</flux:table.cell>
                            <flux:table.cell>{{ $item->variant?->sku }}</flux:table.cell>
                            <flux:table.cell>{{ $item->quantity_on_hand }}</flux:table.cell>
                            <flux:table.cell>{{ $item->quantity_reserved }}</flux:table.cell>
                            <flux:table.cell>{{ $item->available() }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <input type="number" wire:model="adjustments.{{ $item->id }}" class="w-20 rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                                    <flux:button size="xs" wire:click="adjust({{ $item->id }})">Set</flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</div>
