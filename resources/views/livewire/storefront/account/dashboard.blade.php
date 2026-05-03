<div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-3xl font-semibold tracking-normal">Welcome back, {{ str($customer->name ?? $customer->email)->before(' ') }}</h1>
        <button type="button" wire:click="logout" class="w-max rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold dark:border-zinc-700">
            Log out
        </button>
    </div>

    <div class="mt-8 grid gap-4 md:grid-cols-2">
        <a href="{{ route('storefront.account.orders.index') }}" class="rounded-lg border border-zinc-200 p-5 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900">
            <div class="text-base font-semibold">Order history</div>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">View all your orders</p>
        </a>
        <a href="{{ route('storefront.account.addresses.index') }}" class="rounded-lg border border-zinc-200 p-5 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900">
            <div class="text-base font-semibold">Addresses</div>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Manage your addresses</p>
        </a>
    </div>

    <section class="mt-10">
        <h2 class="text-xl font-semibold tracking-normal">Recent orders</h2>
        <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800">
            @if($recentOrders->isEmpty())
                <p class="p-5 text-sm text-zinc-600 dark:text-zinc-400">No orders yet.</p>
            @else
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Order</th>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Total</th>
                            <th class="px-4 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach($recentOrders as $order)
                            <tr wire:key="recent-order-{{ $order->id }}">
                                <td class="px-4 py-3 font-medium">{{ $order->order_number }}</td>
                                <td class="px-4 py-3">{{ $order->placed_at?->format('M j, Y') }}</td>
                                <td class="px-4 py-3">{{ str_replace('_', ' ', $order->status->value) }}</td>
                                <td class="px-4 py-3 text-right">@include('storefront.components.price', ['amount' => $order->total_amount, 'currency' => $order->currency])</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('storefront.account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}" class="font-semibold underline underline-offset-4">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
</div>
