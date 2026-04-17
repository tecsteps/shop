@extends('storefront.layout')

@section('title', 'Order '.$order->order_number)

@section('content')
<a href="{{ route('account.orders.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Back to orders</a>

<h1 class="mt-2 text-2xl font-semibold text-slate-900">Order {{ $order->order_number }}</h1>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <h2 class="text-lg font-semibold text-slate-900">Items</h2>
        <ul class="mt-4 space-y-2">
            @foreach ($order->lines as $line)
                <li class="rounded border border-slate-200 px-3 py-2 text-sm">
                    <span class="font-medium text-slate-900">{{ $line->title_snapshot }}</span>
                    <span class="ml-2 text-slate-500">x{{ (int) $line->quantity }}</span>
                    <span class="float-right text-slate-800">{{ number_format(((int) $line->total_amount) / 100, 2, '.', ',') }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-sm font-semibold text-slate-900">Summary</h2>
        <dl class="mt-3 space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd>{{ number_format(((int) $order->subtotal_amount) / 100, 2, '.', ',') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd>-{{ number_format(((int) $order->discount_amount) / 100, 2, '.', ',') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Shipping</dt><dd>{{ number_format(((int) $order->shipping_amount) / 100, 2, '.', ',') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Tax</dt><dd>{{ number_format(((int) $order->tax_amount) / 100, 2, '.', ',') }}</dd></div>
            <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold"><dt>Total</dt><dd>{{ number_format(((int) $order->total_amount) / 100, 2, '.', ',') }} {{ strtoupper($order->currency) }}</dd></div>
        </dl>
    </div>
</div>
@endsection
