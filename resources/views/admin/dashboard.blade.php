@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Orders</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($ordersCount) }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Revenue</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($revenue / 100, 2, '.', ',') }} {{ strtoupper($store->default_currency) }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Customers</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($customersCount) }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">Products</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($productsCount) }}</p>
    </div>
</div>

<div class="mt-8 rounded-xl border border-slate-200 bg-white">
    <div class="border-b border-slate-200 px-4 py-3">
        <h2 class="text-sm font-semibold text-slate-900">Recent Orders</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-2 text-left">Order</th>
                    <th class="px-4 py-2 text-left">Customer</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentOrders as $order)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-2"><a class="text-slate-800 hover:text-slate-900" href="{{ route('admin.orders.show', $order->id) }}">{{ $order->order_number }}</a></td>
                        <td class="px-4 py-2">{{ $order->email ?? 'Guest' }}</td>
                        <td class="px-4 py-2">{{ is_object($order->status) ? $order->status->value : $order->status }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format(((int) $order->total_amount) / 100, 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">No orders yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
