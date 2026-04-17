<div class="flex flex-col items-center gap-6 py-16 text-center">
    <h1 class="text-4xl font-semibold tracking-tight">Order confirmed</h1>
    @if ($order)
        <p class="text-neutral-700 dark:text-neutral-200">
            Order number: <span class="font-semibold">{{ $order->order_number }}</span>
        </p>
        <p class="max-w-md text-neutral-600 dark:text-neutral-400">
            Total: {{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}
        </p>
    @else
        <p class="max-w-md text-neutral-600 dark:text-neutral-400">
            Thanks for your purchase. A confirmation email is on its way.
        </p>
    @endif
    <a href="{{ url('/') }}" class="inline-block rounded-full bg-neutral-900 px-6 py-3 text-sm font-semibold text-white hover:bg-neutral-700">
        Continue shopping
    </a>
</div>
