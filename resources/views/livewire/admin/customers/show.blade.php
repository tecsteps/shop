<div>
    <x-slot:title>{{ $customer->name }}</x-slot:title>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $customer->name }}</flux:heading>
        <flux:button href="{{ route('admin.customers.index') }}" variant="ghost" wire:navigate>Back to Customers</flux:button>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            {{-- Recent Orders --}}
            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <flux:heading size="lg">Recent Orders</flux:heading>
                </div>
                @if ($customer->orders->isEmpty())
                    <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No orders yet.</div>
                @else
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($customer->orders as $order)
                            <div class="flex items-center justify-between px-6 py-4">
                                <div>
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-blue-600 hover:underline" wire:navigate>
                                        {{ $order->order_number }}
                                    </a>
                                    <p class="text-sm text-gray-500">{{ $order->placed_at?->format('M d, Y') ?? '-' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium">{{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</p>
                                    <flux:badge size="sm" :color="match($order->status->value) { 'paid' => 'green', 'fulfilled' => 'green', 'cancelled' => 'red', default => 'yellow' }">
                                        {{ ucfirst($order->status->value) }}
                                    </flux:badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <flux:heading size="lg">Customer Info</flux:heading>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Email</dt>
                        <dd>{{ $customer->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Marketing</dt>
                        <dd>{{ $customer->marketing_opt_in ? 'Subscribed' : 'Not subscribed' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Joined</dt>
                        <dd>{{ $customer->created_at->format('M d, Y') }}</dd>
                    </div>
                </dl>
            </div>

            @if ($customer->addresses->isNotEmpty())
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <flux:heading size="lg">Addresses</flux:heading>
                    <div class="mt-4 space-y-3">
                        @foreach ($customer->addresses as $address)
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                @if ($address->label)
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $address->label }}</p>
                                @endif
                                <address class="not-italic">
                                    {{ $address->address_json['first_name'] ?? '' }} {{ $address->address_json['last_name'] ?? '' }}<br>
                                    {{ $address->address_json['address1'] ?? '' }}<br>
                                    {{ $address->address_json['zip'] ?? '' }} {{ $address->address_json['city'] ?? '' }}
                                </address>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
