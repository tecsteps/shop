<div class="space-y-6 p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Analytics</flux:heading>
        <div class="flex gap-2">
            <flux:field>
                <flux:label class="sr-only">Start date</flux:label>
                <flux:input type="date" wire:model.live="startDate" />
            </flux:field>
            <flux:field>
                <flux:label class="sr-only">End date</flux:label>
                <flux:input type="date" wire:model.live="endDate" />
            </flux:field>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs text-zinc-500">Revenue</p>
            <p class="mt-2 text-2xl font-bold">{{ number_format($totals['revenue'] / 100, 2) }} {{ $currency }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs text-zinc-500">Orders</p>
            <p class="mt-2 text-2xl font-bold">{{ number_format($totals['orders']) }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs text-zinc-500">AOV</p>
            <p class="mt-2 text-2xl font-bold">{{ number_format($totals['aov'] / 100, 2) }} {{ $currency }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs text-zinc-500">Visits</p>
            <p class="mt-2 text-2xl font-bold">{{ number_format($totals['visits']) }}</p>
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="p-5">
            <flux:heading size="lg">Daily breakdown</flux:heading>
        </div>
        @if ($metrics->isEmpty())
            <div class="p-12 text-center text-sm text-zinc-500">No data for this range.</div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column>Orders</flux:table.column>
                    <flux:table.column>Revenue</flux:table.column>
                    <flux:table.column>AOV</flux:table.column>
                    <flux:table.column>Visits</flux:table.column>
                    <flux:table.column>Add to cart</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($metrics as $row)
                        <flux:table.row>
                            <flux:table.cell>{{ $row->date?->toDateString() }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($row->orders_count) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($row->revenue_amount / 100, 2) }} {{ $currency }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($row->aov_amount / 100, 2) }} {{ $currency }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($row->visits_count) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($row->add_to_cart_count) }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</div>
