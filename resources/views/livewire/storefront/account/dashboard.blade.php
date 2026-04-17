<div class="flex flex-col gap-8">
    <header class="flex flex-col gap-2">
        <h1 class="text-3xl font-semibold tracking-tight">Hello, {{ $customer->name ?? $customer->email }}</h1>
        <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $customer->email }}</p>
    </header>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <a href="{{ url('/account/orders') }}" class="rounded-lg border border-neutral-200 bg-white p-5 hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-600">
            <h2 class="font-semibold">Orders</h2>
            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Review your order history.</p>
        </a>
        <a href="{{ url('/account/addresses') }}" class="rounded-lg border border-neutral-200 bg-white p-5 hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-600">
            <h2 class="font-semibold">Addresses</h2>
            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Manage your shipping addresses.</p>
        </a>
        <a href="{{ url('/account/profile') }}" class="rounded-lg border border-neutral-200 bg-white p-5 hover:border-neutral-400 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-600">
            <h2 class="font-semibold">Profile</h2>
            <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">Update your contact information.</p>
        </a>
    </section>

    <section class="flex flex-col gap-4">
        <div class="flex items-end justify-between">
            <h2 class="text-xl font-semibold">Recent orders</h2>
            <a href="{{ url('/account/orders') }}" class="text-sm font-medium text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">View all</a>
        </div>

        @if ($recentOrders->isEmpty())
            <p class="text-sm text-neutral-500 dark:text-neutral-400">You do not have any orders yet.</p>
        @else
            <ul class="divide-y divide-neutral-200 rounded-lg border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-800">
                @foreach ($recentOrders as $order)
                    <li wire:key="order-{{ $order->id }}" class="flex items-center justify-between gap-4 p-4">
                        <div>
                            <a href="{{ url('/account/orders/'.$order->order_number) }}" class="font-medium hover:underline">
                                Order {{ $order->order_number }}
                            </a>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                {{ optional($order->placed_at)->format('M j, Y') }}
                            </p>
                        </div>
                        <div class="text-sm font-semibold">
                            {{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <form method="POST" action="{{ url('/account/logout') }}">
        @csrf
        <button type="submit" class="text-sm font-medium text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">Sign out</button>
    </form>
</div>
