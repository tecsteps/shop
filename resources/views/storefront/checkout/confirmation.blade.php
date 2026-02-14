@extends('storefront.layout')

@section('title', 'Checkout Confirmation #'.$checkout->id.' | '.($currentStore->name ?? config('app.name')))

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Checkout Confirmation</h1>
        <p class="mt-2 text-sm text-slate-600">Checkout #{{ $checkout->id }} is currently <span class="font-medium text-slate-800">{{ str_replace('_', ' ', $status) }}</span>.</p>

        @if ($order)
            <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                Order #{{ $order->order_number }} was matched to this completed checkout.
            </div>
        @endif

        <dl class="mt-6 grid gap-2 text-sm sm:max-w-md">
            <div class="flex items-center justify-between text-slate-600">
                <dt>Subtotal</dt>
                <dd>{{ number_format(((int) $totals['subtotal_amount']) / 100, 2, '.', ',') }} {{ strtoupper((string) $totals['currency']) }}</dd>
            </div>
            <div class="flex items-center justify-between text-slate-600">
                <dt>Discounts</dt>
                <dd>-{{ number_format(((int) $totals['discount_amount']) / 100, 2, '.', ',') }} {{ strtoupper((string) $totals['currency']) }}</dd>
            </div>
            <div class="flex items-center justify-between text-slate-600">
                <dt>Shipping</dt>
                <dd>{{ number_format(((int) $totals['shipping_amount']) / 100, 2, '.', ',') }} {{ strtoupper((string) $totals['currency']) }}</dd>
            </div>
            <div class="flex items-center justify-between text-slate-600">
                <dt>Tax</dt>
                <dd>{{ number_format(((int) $totals['tax_amount']) / 100, 2, '.', ',') }} {{ strtoupper((string) $totals['currency']) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-slate-200 pt-2 font-semibold text-slate-900">
                <dt>Total</dt>
                <dd>{{ number_format(((int) $totals['total_amount']) / 100, 2, '.', ',') }} {{ strtoupper((string) $totals['currency']) }}</dd>
            </div>
        </dl>
    </section>
@endsection
