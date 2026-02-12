@extends('storefront.layout')

@section('content')
<section class="mx-auto max-w-4xl space-y-6">
    <header class="rounded-2xl border border-zinc-200 bg-white px-6 py-8 text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
            <svg class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.414 0l-3-3a1 1 0 011.414-1.42L8.8 11.786l6.493-6.495a1 1 0 011.411 0z" clip-rule="evenodd"/></svg>
        </div>
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Thank you for your order!</h1>
        <p class="mt-2 text-sm text-zinc-600">Order {{ $order->order_number }}</p>
        @if ($order->email)
            <p class="mt-1 text-sm text-zinc-500">A confirmation was sent to {{ $order->email }}.</p>
        @endif
    </header>

    <section class="rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-zinc-900">Order Summary</h2>
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

    <section class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-6 md:grid-cols-2">
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">Shipping Address</h2>
            <div class="mt-2 space-y-1 text-sm text-zinc-700">
                @foreach ($shippingAddressLines as $line)
                    <p>{{ $line }}</p>
                @endforeach
            </div>
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">Payment Method</h2>
            <p class="mt-2 text-sm text-zinc-700">
                @if ($order->payment_method === \App\Enums\PaymentMethod::CreditCard)
                    Credit Card
                @elseif ($order->payment_method === \App\Enums\PaymentMethod::Paypal)
                    PayPal
                @else
                    Bank Transfer
                @endif
            </p>
            <p class="mt-1 text-xs text-zinc-500">Financial status: {{ ucfirst($order->financial_status->value) }}</p>
        </div>
    </section>

    @if ($bankTransferPending)
        <section class="rounded-2xl border border-blue-200 bg-blue-50 p-6">
            <h2 class="text-lg font-semibold text-blue-900">Bank Transfer Instructions</h2>
            <p class="mt-3 text-sm text-blue-900">Please transfer the full order total within 7 days.</p>
            <dl class="mt-4 space-y-2 text-sm text-blue-900">
                <div class="flex items-center justify-between gap-3"><dt class="font-medium">Bank</dt><dd>Mock Bank AG</dd></div>
                <div class="flex items-center justify-between gap-3"><dt class="font-medium">IBAN</dt><dd>DE89 3704 0044 0532 0130 00</dd></div>
                <div class="flex items-center justify-between gap-3"><dt class="font-medium">BIC</dt><dd>COBADEFFXXX</dd></div>
                <div class="flex items-center justify-between gap-3"><dt class="font-medium">Amount</dt><dd>{{ number_format($order->total_amount / 100, 2, '.', ',') }} {{ $order->currency }}</dd></div>
                <div class="flex items-center justify-between gap-3"><dt class="font-medium">Reference</dt><dd>{{ $order->order_number }}</dd></div>
            </dl>
        </section>
    @endif

    <section class="rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-zinc-900">Totals</h2>
        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex items-center justify-between"><dt class="text-zinc-600">Subtotal</dt><dd class="font-medium text-zinc-900">{{ number_format($order->subtotal_amount / 100, 2, '.', ',') }} {{ $order->currency }}</dd></div>
            @if ($order->discount_amount > 0)
                <div class="flex items-center justify-between"><dt class="text-zinc-600">Discount</dt><dd class="font-medium text-emerald-700">-{{ number_format($order->discount_amount / 100, 2, '.', ',') }} {{ $order->currency }}</dd></div>
            @endif
            <div class="flex items-center justify-between"><dt class="text-zinc-600">Shipping</dt><dd class="font-medium text-zinc-900">{{ number_format($order->shipping_amount / 100, 2, '.', ',') }} {{ $order->currency }}</dd></div>
            <div class="flex items-center justify-between"><dt class="text-zinc-600">Tax</dt><dd class="font-medium text-zinc-900">{{ number_format($order->tax_amount / 100, 2, '.', ',') }} {{ $order->currency }}</dd></div>
            <div class="flex items-center justify-between border-t border-zinc-200 pt-2"><dt class="text-base font-semibold text-zinc-900">Total</dt><dd class="text-base font-semibold text-zinc-900">{{ number_format($order->total_amount / 100, 2, '.', ',') }} {{ $order->currency }}</dd></div>
        </dl>
    </section>

    <div class="flex flex-wrap justify-center gap-3">
        <a href="{{ route('home') }}" class="rounded-md bg-zinc-900 px-5 py-3 text-sm font-semibold text-white hover:bg-zinc-700">Continue shopping</a>
        @if ($canViewOrder)
            <a href="{{ route('storefront.account.orders.show', ['orderNumber' => $order->order_number]) }}" class="rounded-md border border-zinc-300 px-5 py-3 text-sm font-semibold text-zinc-700 hover:bg-zinc-100">View order</a>
        @endif
    </div>
</section>
@endsection
