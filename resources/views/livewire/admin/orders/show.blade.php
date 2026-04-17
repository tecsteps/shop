<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold tracking-tight">Order {{ $order->order_number }}</h1>
        <a href="{{ url('/admin/orders') }}" class="text-sm text-neutral-500 hover:text-neutral-900 dark:hover:text-white">Back to orders</a>
    </div>

    <section>
        <h2 class="text-lg font-semibold">Items</h2>
        <ul class="mt-2 divide-y divide-neutral-200 text-sm dark:divide-neutral-800">
            @foreach ($order->lines as $line)
                <li wire:key="line-{{ $line->id }}" class="flex justify-between py-2">
                    <span>{{ $line->title_snapshot }} &times; {{ $line->quantity }}</span>
                    <span>{{ number_format($line->total_amount / 100, 2) }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="grid grid-cols-2 gap-4 text-sm">
        <div><span class="text-neutral-500">Status:</span> {{ $order->status->value }}</div>
        <div><span class="text-neutral-500">Financial:</span> {{ str_replace('_', ' ', $order->financial_status->value) }}</div>
        <div><span class="text-neutral-500">Fulfillment:</span> {{ $order->fulfillment_status->value }}</div>
        <div><span class="text-neutral-500">Total:</span> {{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</div>
    </section>

    <section>
        <h2 class="text-lg font-semibold">Fulfillments</h2>
        @if ($order->fulfillments->isEmpty())
            <p class="text-sm text-neutral-500">None yet.</p>
        @else
            <ul class="mt-2 divide-y divide-neutral-200 text-sm dark:divide-neutral-800">
                @foreach ($order->fulfillments as $fulfillment)
                    <li wire:key="fulfillment-{{ $fulfillment->id }}" class="py-2">
                        {{ $fulfillment->status->value }}
                        @if ($fulfillment->tracking_number)
                            - {{ $fulfillment->tracking_company }} {{ $fulfillment->tracking_number }}
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
