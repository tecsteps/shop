<div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-3xl font-semibold tracking-normal">Order history</h1>
        <a href="{{ route('storefront.account.dashboard') }}" class="text-sm font-semibold underline underline-offset-4">Account</a>
    </div>

    <div class="mt-8 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800">
        @if($orders->isEmpty())
            <p class="p-5 text-sm text-zinc-600 dark:text-zinc-400">No orders yet.</p>
        @else
            <table class="hidden w-full text-left text-sm md:table">
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
                    @foreach($orders as $order)
                        <tr wire:key="order-row-{{ $order->id }}">
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

            <div class="grid gap-3 p-4 md:hidden">
                @foreach($orders as $order)
                    <a wire:key="order-card-{{ $order->id }}" href="{{ route('storefront.account.orders.show', ['orderNumber' => ltrim($order->order_number, '#')]) }}" class="rounded-md border border-zinc-200 p-4 dark:border-zinc-800">
                        <div class="flex items-center justify-between gap-4">
                            <span class="font-semibold">{{ $order->order_number }}</span>
                            <span>@include('storefront.components.price', ['amount' => $order->total_amount, 'currency' => $order->currency])</span>
                        </div>
                        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $order->placed_at?->format('M j, Y') }} · {{ str_replace('_', ' ', $order->status->value) }}</div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="mt-6">
        {{ $orders->links() }}
    </div>
</div>
