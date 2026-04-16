<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Discounts</flux:heading>
        <flux:button href="{{ route('admin.discounts.create') }}" variant="primary" icon="plus" wire:navigate>New discount</flux:button>
    </div>

    <div class="rounded-xl bg-white ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
        @if ($discounts->isEmpty())
            <div class="p-10 text-center text-zinc-500">No discounts yet.</div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Code</flux:table.column>
                    <flux:table.column>Value</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Usage</flux:table.column>
                    <flux:table.column>Expires</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($discounts as $discount)
                        <flux:table.row>
                            <flux:table.cell><div class="font-medium">{{ $discount->code ?? '—' }}</div></flux:table.cell>
                            <flux:table.cell>
                                @switch($discount->value_type->value)
                                    @case('percent') {{ $discount->value_amount }}% off @break
                                    @case('fixed') {{ number_format($discount->value_amount / 100, 2) }} off @break
                                    @case('free_shipping') Free shipping @break
                                @endswitch
                            </flux:table.cell>
                            <flux:table.cell><flux:badge size="sm" :color="$discount->status->value === 'active' ? 'emerald' : 'zinc'">{{ $discount->status->value }}</flux:badge></flux:table.cell>
                            <flux:table.cell>{{ $discount->usage_count }} / {{ $discount->usage_limit ?? '∞' }}</flux:table.cell>
                            <flux:table.cell>{{ $discount->ends_at?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                            <flux:table.cell><flux:button size="xs" variant="ghost" href="{{ route('admin.discounts.edit', $discount) }}" wire:navigate>Edit</flux:button></flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</div>
