<div class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $customer->fullName() }}</flux:heading>
        <flux:button variant="ghost" href="{{ route('admin.customers.index') }}" wire:navigate>Back</flux:button>
    </div>
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
                <flux:heading size="sm">Orders</flux:heading>
                @if ($orders->isEmpty())
                    <flux:text class="mt-3 text-zinc-500">No orders yet.</flux:text>
                @else
                    <table class="mt-3 w-full text-sm">
                        <thead class="text-zinc-500">
                            <tr>
                                <th class="p-2 text-left">Order</th>
                                <th class="p-2 text-left">Date</th>
                                <th class="p-2 text-left">Status</th>
                                <th class="p-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr wire:key="order-{{ $order->id }}" class="border-t border-zinc-100 dark:border-zinc-800">
                                    <td class="p-2"><a class="text-sky-600 hover:underline" href="{{ route('admin.orders.show', $order) }}" wire:navigate>#{{ $order->order_number }}</a></td>
                                    <td class="p-2">{{ $order->placed_at?->format('Y-m-d') }}</td>
                                    <td class="p-2">{{ $order->financial_status?->value }}</td>
                                    <td class="p-2 text-right">{{ number_format($order->total_amount / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
        <div class="space-y-4">
            <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
                <flux:heading size="sm">Contact</flux:heading>
                <div class="mt-3 space-y-1 text-sm">
                    <div>{{ $customer->email }}</div>
                    <div>{{ $customer->phone }}</div>
                </div>
            </div>
            <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-zinc-900">
                <flux:heading size="sm">Addresses</flux:heading>
                @if ($customer->addresses->isEmpty())
                    <flux:text class="mt-3 text-zinc-500">No addresses saved.</flux:text>
                @else
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($customer->addresses as $addr)
                            <li wire:key="addr-{{ $addr->id }}">{{ $addr->address1 }}, {{ $addr->city }}, {{ $addr->country }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
