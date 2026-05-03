<div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
    <nav class="text-sm text-zinc-600 dark:text-zinc-400">
        <a href="{{ route('storefront.account.dashboard') }}" class="underline underline-offset-4">Account</a>
        <span>/</span>
        <a href="{{ route('storefront.account.orders.index') }}" class="underline underline-offset-4">Orders</a>
        <span>/</span>
        <span>{{ $order->order_number }}</span>
    </nav>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold tracking-normal">Order {{ $order->order_number }}</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Placed on {{ $order->placed_at?->format('F j, Y') }}</p>
        </div>
        <div class="flex gap-2 text-sm">
            <span class="rounded-full border border-zinc-300 px-3 py-1 dark:border-zinc-700">{{ str_replace('_', ' ', $order->status->value) }}</span>
            <span class="rounded-full border border-zinc-300 px-3 py-1 dark:border-zinc-700">{{ str_replace('_', ' ', $order->fulfillment_status->value) }}</span>
        </div>
    </div>

    <section class="mt-8 rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
        <h2 class="text-lg font-semibold tracking-normal">Items</h2>
        <div class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-800">
            @foreach($order->lines as $line)
                <div wire:key="order-line-{{ $line->id }}" class="flex justify-between gap-4 py-4 text-sm">
                    <div>
                        <div class="font-medium">{{ $line->title_snapshot }} × {{ $line->quantity }}</div>
                        @if($line->sku_snapshot)
                            <div class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $line->sku_snapshot }}</div>
                        @endif
                    </div>
                    <div class="font-medium">@include('storefront.components.price', ['amount' => $line->total_amount, 'currency' => $order->currency])</div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="rounded-lg border border-zinc-200 p-5 text-sm dark:border-zinc-800">
            <h2 class="font-semibold">Shipping address</h2>
            @include('storefront.components.address', ['address' => $order->shipping_address_json ?? []])
        </section>
        <section class="rounded-lg border border-zinc-200 p-5 text-sm dark:border-zinc-800">
            <h2 class="font-semibold">Billing address</h2>
            @include('storefront.components.address', ['address' => $order->billing_address_json ?? []])
        </section>
        <section class="rounded-lg border border-zinc-200 p-5 text-sm dark:border-zinc-800">
            <h2 class="font-semibold">Payment</h2>
            <p class="mt-3">{{ str_replace('_', ' ', $order->payment_method->value) }}</p>
            <p class="mt-1 text-zinc-600 dark:text-zinc-400">{{ str_replace('_', ' ', $order->financial_status->value) }}</p>
        </section>
    </div>

    <section class="mt-6 rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
        <h2 class="text-lg font-semibold tracking-normal">Totals</h2>
        <dl class="mt-4 flex flex-col gap-2 text-sm">
            <div class="flex justify-between"><dt>Subtotal</dt><dd>@include('storefront.components.price', ['amount' => $order->subtotal_amount, 'currency' => $order->currency])</dd></div>
            <div class="flex justify-between"><dt>Discount</dt><dd>-@include('storefront.components.price', ['amount' => $order->discount_amount, 'currency' => $order->currency])</dd></div>
            <div class="flex justify-between"><dt>Shipping</dt><dd>@include('storefront.components.price', ['amount' => $order->shipping_amount, 'currency' => $order->currency])</dd></div>
            <div class="flex justify-between"><dt>Tax</dt><dd>@include('storefront.components.price', ['amount' => $order->tax_amount, 'currency' => $order->currency])</dd></div>
            <div class="flex justify-between border-t border-zinc-200 pt-3 text-base font-semibold dark:border-zinc-800"><dt>Total</dt><dd>@include('storefront.components.price', ['amount' => $order->total_amount, 'currency' => $order->currency])</dd></div>
        </dl>
    </section>

    @if($order->fulfillments->isNotEmpty())
        <section class="mt-6 rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
            <h2 class="text-lg font-semibold tracking-normal">Fulfillment</h2>
            <div class="mt-4 grid gap-3">
                @foreach($order->fulfillments as $fulfillment)
                    <div wire:key="fulfillment-{{ $fulfillment->id }}" class="text-sm">
                        <div>{{ str_replace('_', ' ', $fulfillment->status->value) }}</div>
                        @if($fulfillment->tracking_number)
                            <div class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $fulfillment->tracking_company }} · {{ $fulfillment->tracking_number }}</div>
                        @endif
                        @if($fulfillment->tracking_url)
                            <a href="{{ $fulfillment->tracking_url }}" rel="noopener noreferrer" target="_blank" class="mt-2 inline-flex font-semibold underline underline-offset-4">Track shipment</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
