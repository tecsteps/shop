<div>
    <a href="{{ route('admin.customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">&larr; Customers</a>
    <flux:heading size="xl" class="mt-4">{{ $customer->name }}</flux:heading>
    <p class="mt-1 text-sm text-gray-500">{{ $customer->email }}</p>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <flux:heading size="lg">Recent Orders</flux:heading>
            @if($customer->orders->isEmpty())
                <p class="mt-4 text-sm text-gray-500">No orders.</p>
            @else
                <div class="mt-4 space-y-2">
                    @foreach($customer->orders as $order)
                        <div class="flex items-center justify-between text-sm">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $order->order_number }}</a>
                            <span class="text-gray-500">${{ number_format($order->total_amount / 100, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <flux:heading size="lg">Addresses</flux:heading>
            @if($customer->addresses->isEmpty())
                <p class="mt-4 text-sm text-gray-500">No addresses.</p>
            @else
                <div class="mt-4 space-y-3">
                    @foreach($customer->addresses as $address)
                        <div class="rounded border border-gray-100 p-3 text-sm dark:border-gray-700">
                            <p class="font-medium">{{ $address->label }}@if($address->is_default) <flux:badge size="sm">Default</flux:badge>@endif</p>
                            <p class="text-gray-500">{{ $address->address_json['address1'] ?? '' }}, {{ $address->address_json['city'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
