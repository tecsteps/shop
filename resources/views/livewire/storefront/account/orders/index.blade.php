<div class="flex flex-col gap-6">
    <header class="flex flex-col gap-2">
        <h1 class="text-3xl font-semibold tracking-tight">Your orders</h1>
    </header>

    @if ($orders->isEmpty())
        <p class="text-sm text-neutral-500 dark:text-neutral-400">You do not have any orders yet.</p>
    @else
        <ul class="divide-y divide-neutral-200 rounded-lg border border-neutral-200 dark:divide-neutral-800 dark:border-neutral-800">
            @foreach ($orders as $order)
                <li wire:key="order-{{ $order->id }}" class="flex items-center justify-between gap-4 p-4">
                    <div>
                        <a href="{{ url('/account/orders/'.$order->order_number) }}" class="font-medium hover:underline">
                            Order {{ $order->order_number }}
                        </a>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">
                            {{ optional($order->placed_at)->format('M j, Y') }} &middot; {{ $order->status->value }}
                        </p>
                    </div>
                    <div class="text-sm font-semibold">
                        {{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
