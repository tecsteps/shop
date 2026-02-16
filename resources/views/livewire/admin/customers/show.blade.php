<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $customer->first_name }} {{ $customer->last_name }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $customer->email }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Orders --}}
            <div class="rounded-lg border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-zinc-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Order History</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                    <thead class="bg-gray-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                        @forelse($customer->orders as $order)
                            <tr>
                                <td class="px-6 py-4 text-sm"><a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-gray-900 hover:underline dark:text-white">#{{ $order->order_number }}</a></td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $order->placed_at?->format('M d, Y') }}</td>
                                <td class="px-6 py-4"><flux:badge size="sm">{{ ucfirst($order->financial_status?->value ?? 'unknown') }}</flux:badge></td>
                                <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white">${{ number_format($order->total / 100, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Addresses --}}
            <x-admin.card>
                <h3 class="mb-3 font-semibold text-gray-900 dark:text-white">Addresses</h3>
                @forelse($customer->addresses as $address)
                    <div class="mb-3 rounded border border-gray-100 p-3 text-sm text-gray-600 dark:border-zinc-700 dark:text-gray-400">
                        <p>{{ $address->first_name }} {{ $address->last_name }}</p>
                        <p>{{ $address->address1 }}</p>
                        <p>{{ $address->city }}, {{ $address->province }} {{ $address->zip }}</p>
                        <p>{{ $address->country }}</p>
                        @if($address->is_default) <flux:badge size="sm" class="mt-1">Default</flux:badge> @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No addresses.</p>
                @endforelse
            </x-admin.card>
        </div>
    </div>
</div>
