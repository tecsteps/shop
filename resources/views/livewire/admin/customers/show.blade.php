<div>
    {{-- Breadcrumbs --}}
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}">Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.customers.index') }}">Customers</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $customer->full_name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    {{-- Page heading --}}
    <div class="mb-6">
        <flux:heading size="xl">{{ $customer->full_name }}</flux:heading>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Stats --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-zinc-500 dark:text-zinc-400">Total Orders</flux:text>
                    <flux:heading size="xl" class="mt-1">{{ number_format($ordersCount) }}</flux:heading>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-zinc-500 dark:text-zinc-400">Total Spent</flux:text>
                    <flux:heading size="xl" class="mt-1">{{ \App\Support\MoneyFormatter::format($totalSpent) }}</flux:heading>
                </div>
            </div>

            {{-- Recent orders --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">Recent orders</flux:heading>

                @if ($recentOrders->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="pb-2 text-left font-medium text-zinc-500 dark:text-zinc-400">Order #</th>
                                    <th class="pb-2 text-right font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                                    <th class="pb-2 text-left font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                                    <th class="pb-2 text-left font-medium text-zinc-500 dark:text-zinc-400">Financial</th>
                                    <th class="pb-2 text-left font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentOrders as $order)
                                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                        <td class="py-2">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                                #{{ $order->order_number }}
                                            </a>
                                        </td>
                                        <td class="py-2 text-right text-zinc-900 dark:text-zinc-100">
                                            {{ \App\Support\MoneyFormatter::format($order->total_amount) }}
                                        </td>
                                        <td class="py-2">
                                            @php
                                                $statusColor = match($order->status) {
                                                    \App\Enums\OrderStatus::Paid => 'green',
                                                    \App\Enums\OrderStatus::Pending => 'yellow',
                                                    \App\Enums\OrderStatus::Fulfilled => 'blue',
                                                    \App\Enums\OrderStatus::Cancelled => 'red',
                                                    \App\Enums\OrderStatus::Refunded => 'red',
                                                    default => 'zinc',
                                                };
                                            @endphp
                                            <flux:badge size="sm" :color="$statusColor">{{ ucfirst($order->status->value) }}</flux:badge>
                                        </td>
                                        <td class="py-2">
                                            @php
                                                $financialColor = match($order->financial_status) {
                                                    \App\Enums\FinancialStatus::Paid => 'green',
                                                    \App\Enums\FinancialStatus::Pending => 'yellow',
                                                    \App\Enums\FinancialStatus::Authorized => 'blue',
                                                    \App\Enums\FinancialStatus::PartiallyRefunded => 'orange',
                                                    \App\Enums\FinancialStatus::Refunded => 'red',
                                                    \App\Enums\FinancialStatus::Voided => 'zinc',
                                                    default => 'zinc',
                                                };
                                            @endphp
                                            <flux:badge size="sm" :color="$financialColor">{{ str_replace('_', ' ', ucfirst($order->financial_status->value)) }}</flux:badge>
                                        </td>
                                        <td class="py-2 text-zinc-600 dark:text-zinc-400">
                                            {{ $order->created_at->format('M d, Y') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <flux:text class="text-zinc-500">No orders yet.</flux:text>
                @endif
            </div>
        </div>

        {{-- Right column --}}
        <div class="space-y-6">
            {{-- Customer details --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">Customer details</flux:heading>

                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">Name</span>
                        <div class="text-zinc-900 dark:text-zinc-100">{{ $customer->full_name }}</div>
                    </div>
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">Email</span>
                        <div class="text-zinc-900 dark:text-zinc-100">{{ $customer->email }}</div>
                    </div>
                    @if ($customer->phone)
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400">Phone</span>
                            <div class="text-zinc-900 dark:text-zinc-100">{{ $customer->phone }}</div>
                        </div>
                    @endif
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">Customer since</span>
                        <div class="text-zinc-900 dark:text-zinc-100">{{ $customer->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
            </div>

            {{-- Addresses --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">Addresses</flux:heading>

                @forelse ($customer->addresses as $address)
                    <div class="mb-4 last:mb-0 rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                        @if ($address->is_default)
                            <flux:badge size="sm" color="blue" class="mb-2">Default</flux:badge>
                        @endif
                        <div class="space-y-1 text-sm text-zinc-700 dark:text-zinc-300">
                            <div>{{ $address->first_name }} {{ $address->last_name }}</div>
                            @if ($address->company)
                                <div>{{ $address->company }}</div>
                            @endif
                            <div>{{ $address->address1 }}</div>
                            @if ($address->address2)
                                <div>{{ $address->address2 }}</div>
                            @endif
                            <div>{{ $address->zip }} {{ $address->city }}</div>
                            @if ($address->province)
                                <div>{{ $address->province }}</div>
                            @endif
                            <div>{{ $address->country }}</div>
                            @if ($address->phone)
                                <div>{{ $address->phone }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <flux:text class="text-zinc-500">No addresses on file.</flux:text>
                @endforelse
            </div>
        </div>
    </div>
</div>
