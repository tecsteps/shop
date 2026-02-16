<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Account</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $customer->first_name }} {{ $customer->last_name }} - {{ $customer->email }}</p>
        </div>
        <form method="POST" action="{{ url('/account/logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost" size="sm">Logout</flux:button>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <a href="{{ url('/account/orders') }}" class="rounded-lg border border-gray-200 bg-white p-6 hover:border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600">
            <h3 class="font-semibold text-gray-900 dark:text-white">Orders</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">View your order history</p>
        </a>
        <a href="{{ url('/account/addresses') }}" class="rounded-lg border border-gray-200 bg-white p-6 hover:border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600">
            <h3 class="font-semibold text-gray-900 dark:text-white">Addresses</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your saved addresses</p>
        </a>
    </div>

    {{-- Recent orders --}}
    @if($recentOrders->count())
        <div class="mt-8">
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Recent Orders</h2>
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
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
                        @foreach($recentOrders as $order)
                            <tr>
                                <td class="px-6 py-4 text-sm"><a href="{{ url('/account/orders/' . ltrim($order->order_number, '#')) }}" class="font-medium text-gray-900 hover:underline dark:text-white">{{ $order->order_number }}</a></td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $order->placed_at?->format('M d, Y') }}</td>
                                <td class="px-6 py-4"><flux:badge size="sm">{{ ucfirst($order->financial_status?->value ?? 'unknown') }}</flux:badge></td>
                                <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white">${{ number_format($order->total / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
