<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl">Analytics</flux:heading>
        <div class="flex items-center gap-2">
            <flux:input type="date" wire:model.live="startDate" label="From" size="sm" />
            <flux:input type="date" wire:model.live="endDate" label="To" size="sm" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">Revenue</flux:text>
            <flux:heading size="lg">{{ number_format($totals['revenue_amount'] / 100, 2) }}</flux:heading>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">Orders</flux:text>
            <flux:heading size="lg">{{ $totals['orders_count'] }}</flux:heading>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">Visits</flux:text>
            <flux:heading size="lg">{{ $totals['visits_count'] }}</flux:heading>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">AOV</flux:text>
            <flux:heading size="lg">{{ number_format($totals['aov_amount'] / 100, 2) }}</flux:heading>
        </div>
    </div>

    <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
        <flux:heading size="sm">Daily breakdown</flux:heading>
        @if ($metrics->isEmpty())
            <flux:text class="mt-3 text-zinc-500">No data yet.</flux:text>
        @else
            <table class="mt-3 w-full text-sm">
                <thead class="text-zinc-500">
                    <tr>
                        <th class="p-2 text-left">Date</th>
                        <th class="p-2 text-right">Revenue</th>
                        <th class="p-2 text-right">Orders</th>
                        <th class="p-2 text-right">Visits</th>
                        <th class="p-2 text-right">Carts</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($metrics as $row)
                        <tr wire:key="metric-{{ $row->date }}" class="border-t border-zinc-100 dark:border-zinc-800">
                            <td class="p-2">{{ $row->date }}</td>
                            <td class="p-2 text-right">{{ number_format($row->revenue_amount / 100, 2) }}</td>
                            <td class="p-2 text-right">{{ $row->orders_count }}</td>
                            <td class="p-2 text-right">{{ $row->visits_count }}</td>
                            <td class="p-2 text-right">{{ $row->add_to_cart_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
