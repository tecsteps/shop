@extends('admin.layout')

@section('title', 'Order '.$order->order_number)

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-slate-900">Order Summary</h2>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Order Number</dt><dd class="font-medium text-slate-900">{{ $order->order_number }}</dd></div>
                <div><dt class="text-slate-500">Placed At</dt><dd class="text-slate-900">{{ optional($order->placed_at)->toDateTimeString() ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd class="text-slate-900">{{ is_object($order->status) ? $order->status->value : $order->status }}</dd></div>
                <div><dt class="text-slate-500">Financial</dt><dd class="text-slate-900">{{ is_object($order->financial_status) ? $order->financial_status->value : $order->financial_status }}</dd></div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr><th class="px-4 py-2 text-left">Product</th><th class="px-4 py-2 text-left">SKU</th><th class="px-4 py-2 text-right">Qty</th><th class="px-4 py-2 text-right">Total</th></tr>
                </thead>
                <tbody>
                    @foreach ($order->lines as $line)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-2">{{ $line->title_snapshot }}</td>
                            <td class="px-4 py-2">{{ $line->sku_snapshot ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">{{ (int) $line->quantity }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format(((int) $line->total_amount) / 100, 2, '.', ',') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-900">Customer</h2>
            <p class="mt-2 text-sm text-slate-700">{{ $order->customer?->name ?? 'Guest' }}</p>
            <p class="text-sm text-slate-500">{{ $order->email ?? '—' }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-900">Totals</h2>
            <dl class="mt-3 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd>{{ number_format(((int) $order->subtotal_amount) / 100, 2, '.', ',') }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd>-{{ number_format(((int) $order->discount_amount) / 100, 2, '.', ',') }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Shipping</dt><dd>{{ number_format(((int) $order->shipping_amount) / 100, 2, '.', ',') }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Tax</dt><dd>{{ number_format(((int) $order->tax_amount) / 100, 2, '.', ',') }}</dd></div>
                <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold"><dt>Total</dt><dd>{{ number_format(((int) $order->total_amount) / 100, 2, '.', ',') }} {{ strtoupper($order->currency) }}</dd></div>
            </dl>
        </div>
    </div>
</div>
@endsection
