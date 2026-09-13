<div class="mx-auto max-w-7xl px-4 py-8">
    <x-storefront.breadcrumbs :items="[['label' => 'Account', 'url' => route('customer.account')], ['label' => 'Orders']]" />
    <h1 class="mt-4 text-3xl font-bold">Order History</h1>

    @if ($orders->isEmpty())
        <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">You have not placed any orders yet.</p>
    @else
        <div class="mt-6 hidden overflow-x-auto md:block">
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
                        <tr wire:key="order-{{ $order->getKey() }}">
                            <td class="py-3 font-medium">{{ $order->order_number }}</td>
                            <td class="py-3">{{ $order->placed_at?->format('M d, Y') }}</td>
                            <td class="py-3"><x-storefront.badge :text="ucfirst($order->status->value)" /></td>
                            <td class="py-3 text-right">{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</td>
                            <td class="py-3 text-right"><a href="{{ route('customer.orders.show', ['orderNumber' => $order->order_number]) }}" class="text-blue-600 hover:underline dark:text-blue-400">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6 grid gap-3 md:hidden">
            @foreach ($orders as $order)
                <a href="{{ route('customer.orders.show', ['orderNumber' => $order->order_number]) }}" wire:key="order-card-{{ $order->getKey() }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                    <p class="font-medium">{{ $order->order_number }}</p>
                    <p class="text-sm text-zinc-500">{{ $order->placed_at?->format('M d, Y') }}</p>
                    <p class="mt-1"><x-storefront.badge :text="ucfirst($order->status->value)" /></p>
                    <p class="mt-1 text-sm font-semibold">{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</p>
                </a>
            @endforeach
        </div>
        <x-storefront.pagination :paginator="$orders" />
    @endif
</div>
