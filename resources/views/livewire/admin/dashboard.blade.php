<div>
    <flux:heading size="xl" class="mb-6">Dashboard</flux:heading>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        {{-- Total Revenue --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Revenue</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">
                {{ \App\Livewire\Admin\Dashboard::formatMoney($totalRevenue) }}
            </p>
        </div>

        {{-- Total Orders --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Orders</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">
                {{ number_format($totalOrders) }}
            </p>
        </div>

        {{-- Total Customers --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Customers</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">
                {{ number_format($totalCustomers) }}
            </p>
        </div>

        {{-- Total Products --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Active Products</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">
                {{ number_format($totalProducts) }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Recent Orders Table --}}
        <div class="lg:col-span-2 rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Recent Orders</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="px-6 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Order #</th>
                            <th class="px-6 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Customer</th>
                            <th class="px-6 py-3 text-right font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                            <th class="px-6 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="px-6 py-3 text-left font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" wire:navigate>
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-zinc-700 dark:text-zinc-300">
                                    {{ $order->customer?->full_name ?? '-' }}
                                </td>
                                <td class="px-6 py-3 text-right text-zinc-700 dark:text-zinc-300">
                                    {{ \App\Livewire\Admin\Dashboard::formatMoney($order->total_amount) }}
                                </td>
                                <td class="px-6 py-3">
                                    @php
                                        $statusColor = match($order->status) {
                                            \App\Enums\OrderStatus::Pending => 'yellow',
                                            \App\Enums\OrderStatus::Paid => 'blue',
                                            \App\Enums\OrderStatus::Fulfilled => 'green',
                                            \App\Enums\OrderStatus::Cancelled => 'red',
                                            \App\Enums\OrderStatus::Refunded => 'zinc',
                                            default => 'zinc',
                                        };
                                    @endphp
                                    <flux:badge size="sm" :color="$statusColor">
                                        {{ ucfirst($order->status->value) }}
                                    </flux:badge>
                                </td>
                                <td class="px-6 py-3 text-zinc-500 dark:text-zinc-400">
                                    {{ $order->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                    No orders yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="space-y-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Today</h2>

                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Orders today</p>
                        <p class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">
                            {{ number_format($ordersToday) }}
                        </p>
                    </div>

                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Revenue today</p>
                        <p class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">
                            {{ \App\Livewire\Admin\Dashboard::formatMoney($revenueToday) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
