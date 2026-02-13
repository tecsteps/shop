<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $customer->name }}</flux:heading>
        <a href="{{ route('admin.customers.index') }}">
            <flux:button variant="ghost">Back to Customers</flux:button>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="md" class="mb-3">Customer Info</flux:heading>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Email</dt>
                    <dd>{{ $customer->email }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Marketing</dt>
                    <dd>{{ $customer->marketing_opt_in ? 'Opted in' : 'Not opted in' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500 dark:text-zinc-400">Member since</dt>
                    <dd>{{ $customer->created_at->format('M d, Y') }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="md" class="mb-3">Addresses</flux:heading>
            @forelse ($customer->addresses as $address)
                <div wire:key="address-{{ $address->id }}" class="mb-2 rounded bg-zinc-50 p-2 text-sm dark:bg-zinc-900">
                    <p class="font-medium">{{ $address->label }}</p>
                    <p>{{ $address->address_json['address1'] ?? '' }}, {{ $address->address_json['city'] ?? '' }}</p>
                </div>
            @empty
                <flux:text class="text-sm">No addresses saved.</flux:text>
            @endforelse
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <flux:heading size="md">Orders</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="px-4 py-3 font-medium">Order</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customer->orders as $order)
                        <tr wire:key="order-{{ $order->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $order->order_number }}</a>
                            </td>
                            <td class="px-4 py-3"><flux:badge size="sm">{{ ucfirst($order->status->value) }}</flux:badge></td>
                            <td class="px-4 py-3 text-right">${{ number_format($order->total_amount / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
