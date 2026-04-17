<div class="space-y-8">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">My Account</flux:heading>
        <form method="POST" action="{{ route('storefront.account.logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost" size="sm">Sign Out</flux:button>
        </form>
    </div>

    {{-- Profile Section --}}
    <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
        <flux:heading size="lg" class="mb-4">Profile</flux:heading>

        @if (session('message'))
            <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400">
                {{ session('message') }}
            </div>
        @endif

        <form wire:submit="updateProfile" class="space-y-4">
            <flux:input wire:model="name" label="Name" required />

            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                Email: {{ $customer->email }}
            </div>

            <flux:checkbox wire:model="marketingOptIn" label="Receive marketing emails" />

            <flux:button type="submit" variant="primary">Update Profile</flux:button>
        </form>
    </div>

    {{-- Quick Links --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <a href="{{ route('storefront.account.orders.index') }}" class="rounded-lg border border-zinc-200 p-4 transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
            <flux:heading size="md">Orders</flux:heading>
            <flux:text class="mt-1">View your order history</flux:text>
        </a>
        <a href="{{ route('storefront.account.addresses.index') }}" class="rounded-lg border border-zinc-200 p-4 transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
            <flux:heading size="md">Addresses</flux:heading>
            <flux:text class="mt-1">Manage your saved addresses</flux:text>
        </a>
    </div>

    {{-- Recent Orders --}}
    <div class="rounded-lg border border-zinc-200 p-6 dark:border-zinc-700">
        <flux:heading size="lg" class="mb-4">Recent Orders</flux:heading>

        @if ($recentOrders->isEmpty())
            <flux:text>No orders yet.</flux:text>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                            <th class="px-3 py-2 font-medium">Order</th>
                            <th class="px-3 py-2 font-medium">Date</th>
                            <th class="px-3 py-2 font-medium">Status</th>
                            <th class="px-3 py-2 font-medium text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentOrders as $order)
                            <tr wire:key="order-{{ $order->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                                <td class="px-3 py-2">
                                    <a href="{{ route('storefront.account.orders.show', $order->order_number) }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-3 py-2">{{ $order->placed_at?->format('M d, Y') ?? $order->created_at->format('M d, Y') }}</td>
                                <td class="px-3 py-2">
                                    <flux:badge size="sm">{{ ucfirst($order->status->value) }}</flux:badge>
                                </td>
                                <td class="px-3 py-2 text-right">${{ number_format($order->total_amount / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
