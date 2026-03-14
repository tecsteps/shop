<div>
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('admin.customers.index') }}" wire:navigate>Customers</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $customer->name ?? $customer->email }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left column (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Customer info --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="lg" class="mb-4">{{ $customer->name ?? 'Unnamed Customer' }}</flux:heading>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-zinc-500 dark:text-zinc-400">Email</p>
                        <p class="text-zinc-900 dark:text-white">{{ $customer->email }}</p>
                    </div>
                    <div>
                        <p class="text-zinc-500 dark:text-zinc-400">Created</p>
                        <p class="text-zinc-900 dark:text-white">{{ $customer->created_at->format('M j, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-zinc-500 dark:text-zinc-400">Marketing</p>
                        <flux:badge color="{{ $customer->marketing_opt_in ? 'green' : 'zinc' }}" size="sm">
                            {{ $customer->marketing_opt_in ? 'Opted In' : 'Opted Out' }}
                        </flux:badge>
                    </div>
                    <div>
                        <p class="text-zinc-500 dark:text-zinc-400">Total Orders</p>
                        <p class="text-zinc-900 dark:text-white font-medium">{{ $this->totalOrders }}</p>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 flex gap-6 text-sm">
                    <div>
                        <p class="text-zinc-500 dark:text-zinc-400">Total Spent</p>
                        <p class="text-lg font-bold text-zinc-900 dark:text-white">{{ number_format($this->totalSpent / 100, 2) }} EUR</p>
                    </div>
                </div>
            </div>

            {{-- Order history --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="md" class="mb-4">Order History</flux:heading>
                <flux:separator class="mb-4" />

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="text-left px-2 py-2 font-medium text-zinc-500 dark:text-zinc-400">Order #</th>
                                <th class="text-left px-2 py-2 font-medium text-zinc-500 dark:text-zinc-400">Date</th>
                                <th class="text-left px-2 py-2 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                                <th class="text-right px-2 py-2 font-medium text-zinc-500 dark:text-zinc-400">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($this->orders as $order)
                                <tr wire:key="customer-order-{{ $order->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-2 py-3">
                                        <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                            #{{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td class="px-2 py-3 text-zinc-600 dark:text-zinc-400">
                                        {{ $order->placed_at?->format('M j, Y') ?? '-' }}
                                    </td>
                                    <td class="px-2 py-3">
                                        @php
                                            $statusColor = match($order->status) {
                                                \App\Enums\OrderStatus::Paid => 'green',
                                                \App\Enums\OrderStatus::Fulfilled => 'green',
                                                \App\Enums\OrderStatus::Cancelled => 'red',
                                                \App\Enums\OrderStatus::Refunded => 'yellow',
                                                default => 'zinc',
                                            };
                                        @endphp
                                        <flux:badge color="{{ $statusColor }}" size="sm">
                                            {{ ucfirst($order->status->value) }}
                                        </flux:badge>
                                    </td>
                                    <td class="px-2 py-3 text-right font-medium text-zinc-900 dark:text-white">
                                        {{ number_format($order->total_amount / 100, 2) }} {{ $order->currency ?? 'EUR' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-2 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                        No orders yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($this->orders->hasPages())
                    <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        {{ $this->orders->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Right column (1/3) --}}
        <div class="space-y-6">
            {{-- Addresses --}}
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                <flux:heading size="md" class="mb-4">Addresses</flux:heading>
                <flux:separator class="mb-4" />

                @forelse ($customer->addresses as $address)
                    <div wire:key="address-{{ $address->id }}" class="mb-4 pb-4 border-b border-zinc-100 dark:border-zinc-700 last:border-0 last:mb-0 last:pb-0">
                        <div class="flex items-center gap-2 mb-1">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $address->first_name }} {{ $address->last_name }}
                            </p>
                            @if ($address->is_default)
                                <flux:badge color="green" size="sm">Default</flux:badge>
                            @endif
                        </div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 space-y-0.5">
                            <p>{{ $address->address1 }}</p>
                            @if ($address->address2)
                                <p>{{ $address->address2 }}</p>
                            @endif
                            <p>{{ $address->city }}{{ $address->province ? ', ' . $address->province : '' }} {{ $address->postal_code }}</p>
                            <p>{{ $address->country_code }}</p>
                            @if ($address->phone)
                                <p>{{ $address->phone }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">No addresses on file.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
