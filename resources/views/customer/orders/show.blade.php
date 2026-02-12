@extends('storefront.layout')

@section('content')
<section class="space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Order {{ $order->order_number }}</h1>
            <p class="mt-2 text-sm text-zinc-600">Placed {{ optional($order->placed_at)->format('M d, Y H:i') ?: '-' }}</p>
        </div>
        <a href="{{ route('storefront.account.orders.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">Back to orders</a>
    </header>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <section class="rounded-xl border border-zinc-200 bg-white p-5">
            <h2 class="text-xl font-semibold text-zinc-900">Items</h2>
            <div class="mt-4 divide-y divide-zinc-200">
                @foreach ($order->lines as $line)
                    <div class="flex items-start justify-between gap-3 py-3 text-sm">
                        <div>
                            <p class="font-medium text-zinc-900">{{ $line->title_snapshot }}</p>
                            @if ($line->sku_snapshot)
                                <p class="text-xs text-zinc-500">SKU: {{ $line->sku_snapshot }}</p>
                            @endif
                            <p class="text-xs text-zinc-500">Quantity: {{ $line->quantity }}</p>
                        </div>
                        <p class="font-semibold text-zinc-900">{{ number_format($line->total_amount / 100, 2, '.', ',') }} {{ $order->currency }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="space-y-4">
            <section class="rounded-xl border border-zinc-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-zinc-900">Status</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between"><dt class="text-zinc-600">Order</dt><dd class="font-medium text-zinc-900">{{ ucfirst($order->status->value) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-zinc-600">Financial</dt><dd class="font-medium text-zinc-900">{{ ucfirst($order->financial_status->value) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-zinc-600">Fulfillment</dt><dd class="font-medium text-zinc-900">{{ ucfirst($order->fulfillment_status->value) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-zinc-600">Payment method</dt><dd class="font-medium text-zinc-900">{{ ucfirst(str_replace('_', ' ', $order->payment_method->value)) }}</dd></div>
                </dl>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-zinc-900">Totals</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between"><dt class="text-zinc-600">Subtotal</dt><dd class="font-medium text-zinc-900">{{ number_format($order->subtotal_amount / 100, 2, '.', ',') }} {{ $order->currency }}</dd></div>
                    @if ($order->discount_amount > 0)
                        <div class="flex items-center justify-between"><dt class="text-zinc-600">Discount</dt><dd class="font-medium text-emerald-700">-{{ number_format($order->discount_amount / 100, 2, '.', ',') }} {{ $order->currency }}</dd></div>
                    @endif
                    <div class="flex items-center justify-between"><dt class="text-zinc-600">Shipping</dt><dd class="font-medium text-zinc-900">{{ number_format($order->shipping_amount / 100, 2, '.', ',') }} {{ $order->currency }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-zinc-600">Tax</dt><dd class="font-medium text-zinc-900">{{ number_format($order->tax_amount / 100, 2, '.', ',') }} {{ $order->currency }}</dd></div>
                    <div class="flex items-center justify-between border-t border-zinc-200 pt-2"><dt class="text-base font-semibold text-zinc-900">Total</dt><dd class="text-base font-semibold text-zinc-900">{{ number_format($order->total_amount / 100, 2, '.', ',') }} {{ $order->currency }}</dd></div>
                </dl>
            </section>
        </aside>
    </div>
</section>
@endsection
