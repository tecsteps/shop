<div>
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Analytics</flux:heading>
        <flux:select wire:model.live="dateRange" class="w-44">
            <option value="last_7_days">Last 7 days</option>
            <option value="last_30_days">Last 30 days</option>
            <option value="last_90_days">Last 90 days</option>
        </flux:select>
    </div>

    @if($data->isEmpty())
        <p class="mt-8 text-sm text-gray-500 dark:text-gray-400">No analytics data for this period.</p>
    @else
        @php
            $totalRevenue = $data->sum('revenue_amount');
            $totalOrders = $data->sum('orders_count');
            $totalVisits = $data->sum('visits_count');
            $avgAov = $totalOrders > 0 ? intdiv($totalRevenue, $totalOrders) : 0;
        @endphp

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Revenue</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($totalRevenue / 100, 2) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Orders</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalOrders) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">AOV</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($avgAov / 100, 2) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">Visits</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalVisits) }}</p>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Revenue</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Orders</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Visits</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Add to Cart</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @foreach($data as $row)
                        <tr wire:key="analytics-{{ $row->date }}">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $row->date }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-400">${{ number_format($row->revenue_amount / 100, 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-400">{{ $row->orders_count }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-400">{{ $row->visits_count }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-400">{{ $row->add_to_cart_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
