<div>
    {{-- Date range selector --}}
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Overview</h2>
        <flux:select wire:model.live="dateRange" class="w-40">
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 90 days</option>
        </flux:select>
    </div>

    {{-- KPI tiles --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.card>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sales</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($totalSales / 100, 2) }}</p>
        </x-admin.card>
        <x-admin.card>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Orders</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $orderCount }}</p>
        </x-admin.card>
        <x-admin.card>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Order Value</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($averageOrderValue / 100, 2) }}</p>
        </x-admin.card>
        <x-admin.card>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Conversion Rate</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">--</p>
        </x-admin.card>
    </div>

    {{-- Recent orders --}}
    <div class="rounded-lg border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-zinc-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Recent Orders</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                <a href="{{ route('admin.orders.show', $order) }}" class="hover:underline">#{{ $order->order_number }}</a>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $order->placed_at?->format('M d, Y') ?? $order->created_at->format('M d, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $order->email }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <flux:badge size="sm" :variant="match($order->financial_status?->value) { 'paid' => 'primary', 'pending' => 'warning', 'refunded' => 'danger', default => 'outline' }">
                                    {{ ucfirst($order->financial_status?->value ?? 'unknown') }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-900 dark:text-white">
                                ${{ number_format($order->total / 100, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No orders yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
