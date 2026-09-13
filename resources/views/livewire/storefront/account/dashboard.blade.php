<div class="mx-auto max-w-7xl px-4 py-8">
    <h1 class="text-3xl font-bold">Welcome back, {{ $customer->name ?? 'friend' }}!</h1>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <a href="{{ route('customer.orders.index') }}" class="rounded-lg border border-zinc-200 p-6 hover:shadow dark:border-zinc-800">
            <flux:icon.shopping-bag class="size-6" variant="outline" />
            <p class="mt-2 font-semibold">Order history</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">View all your orders ({{ $orderCount }})</p>
        </a>
        <a href="{{ route('customer.addresses') }}" class="rounded-lg border border-zinc-200 p-6 hover:shadow dark:border-zinc-800">
            <flux:icon.map-pin class="size-6" variant="outline" />
            <p class="mt-2 font-semibold">Addresses</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage your addresses ({{ $addressCount }})</p>
        </a>
        <a href="{{ route('customer.profile') }}" class="rounded-lg border border-zinc-200 p-6 hover:shadow dark:border-zinc-800">
            <flux:icon.user class="size-6" variant="outline" />
            <p class="mt-2 font-semibold">Profile</p>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Update your profile</p>
        </a>
    </div>

    <h2 class="mt-8 text-xl font-semibold">Recent Orders</h2>
    @if ($orders->isEmpty())
        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">You have not placed any orders yet.</p>
    @else
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wide text-zinc-500">
                        <th scope="col" class="py-2">Order</th>
                        <th scope="col" class="py-2">Date</th>
                        <th scope="col" class="py-2">Status</th>
                        <th scope="col" class="py-2 text-right">Total</th>
                        <th scope="col" class="py-2 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($orders as $order)
                        <tr wire:key="recent-{{ $order->getKey() }}">
                            <td class="py-3 font-medium">{{ $order->order_number }}</td>
                            <td class="py-3">{{ $order->placed_at?->format('M d, Y') }}</td>
                            <td class="py-3"><x-storefront.badge :text="ucfirst($order->status->value)" /></td>
                            <td class="py-3 text-right">{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</td>
                            <td class="py-3 text-right"><a href="{{ route('customer.orders.show', ['orderNumber' => urlencode($order->order_number)]) }}" class="text-blue-600 hover:underline dark:text-blue-400">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <form method="POST" action="{{ route('customer.logout') }}" class="mt-8">
        @csrf
        <flux:button type="submit" variant="ghost">Log out</flux:button>
    </form>
</div>
