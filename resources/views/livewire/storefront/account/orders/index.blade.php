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
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Account', 'url' => route('storefront.account.dashboard')],
        ['label' => 'Orders', 'url' => null],
    ]" />

    <h1 class="mt-4 text-2xl font-bold text-zinc-900 dark:text-white">Order History</h1>

    @if ($orders->isEmpty())
        <p class="mt-8 text-sm text-zinc-500 dark:text-zinc-400">You haven't placed any orders yet.</p>
    @else
        <!-- Desktop table -->
        <div class="mt-6 hidden overflow-hidden rounded-xl border border-zinc-200 sm:block dark:border-zinc-800">
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
                    @foreach ($orders as $order)
                        <tr wire:key="order-row-{{ $order->id }}">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                                <a href="{{ route('storefront.account.orders.show', $order) }}" wire:navigate class="hover:underline">{{ $order->order_number }}</a>
                            </td>
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

        <!-- Mobile cards -->
        <div class="mt-6 space-y-3 sm:hidden">
            @foreach ($orders as $order)
                <a href="{{ route('storefront.account.orders.show', $order) }}" wire:navigate wire:key="order-card-{{ $order->id }}" class="block rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-zinc-900 dark:text-white">{{ $order->order_number }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusVariant($order->status->value) }}">{{ ucfirst($order->status->value) }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-sm text-zinc-500 dark:text-zinc-400">
                        <span>{{ $order->placed_at?->format('M j, Y') }}</span>
                        <x-storefront.price :amount="$order->total_amount" :currency="$order->currency" />
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $orders->links() }}
        </div>
    @endif
</div>
