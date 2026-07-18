@php
    $statusVariant = fn (string $status) => match ($status) {
        'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
        'paid' => 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300',
        'fulfilled' => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300',
        'cancelled' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
        'refunded' => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
        default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
    };
@endphp

<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Welcome back, {{ $customer->name }}!</h1>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('storefront.account.orders.index') }}" wire:navigate class="rounded-xl border border-zinc-200 p-6 transition-shadow hover:shadow-md dark:border-zinc-800">
            <flux:icon name="clipboard-document-list" class="size-6 text-zinc-500 dark:text-zinc-400" />
            <p class="mt-3 font-semibold text-zinc-900 dark:text-white">Order history</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">View all your orders</p>
        </a>
        <a href="{{ route('storefront.account.addresses.index') }}" wire:navigate class="rounded-xl border border-zinc-200 p-6 transition-shadow hover:shadow-md dark:border-zinc-800">
            <flux:icon name="map-pin" class="size-6 text-zinc-500 dark:text-zinc-400" />
            <p class="mt-3 font-semibold text-zinc-900 dark:text-white">Addresses</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Manage your addresses</p>
        </a>
        <form method="POST" action="{{ route('storefront.account.logout') }}" class="rounded-xl border border-zinc-200 p-6 transition-shadow hover:shadow-md dark:border-zinc-800">
            @csrf
            <button type="submit" class="w-full text-left">
                <flux:icon name="arrow-right-start-on-rectangle" class="size-6 text-zinc-500 dark:text-zinc-400" />
                <p class="mt-3 font-semibold text-zinc-900 dark:text-white">Log out</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Sign out of your account</p>
            </button>
        </form>
    </div>

    <div class="mt-10">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Recent Orders</h2>

        @if ($recentOrders->isEmpty())
            <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">You haven't placed any orders yet.</p>
        @else
            <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:bg-zinc-900 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3" scope="col">Order</th>
                            <th class="px-4 py-3" scope="col">Date</th>
                            <th class="px-4 py-3" scope="col">Status</th>
                            <th class="px-4 py-3" scope="col">Total</th>
                            <th class="px-4 py-3" scope="col"><span class="sr-only">Action</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-900">
                        @foreach ($recentOrders as $order)
                            <tr wire:key="recent-order-{{ $order->id }}">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $order->order_number }}</td>
                                <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y') }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusVariant($order->status->value) }}">{{ ucfirst($order->status->value) }}</span>
                                </td>
                                <td class="px-4 py-3"><x-storefront.price :amount="$order->total_amount" :currency="$order->currency" /></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('storefront.account.orders.show', $order) }}" wire:navigate class="text-blue-600 hover:underline dark:text-blue-400">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
