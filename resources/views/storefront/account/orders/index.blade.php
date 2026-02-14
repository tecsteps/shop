@extends('storefront.layout')

@section('title', 'Order History')

@section('content')
<h1 class="text-2xl font-semibold text-slate-900">Your Orders</h1>

<div class="mt-6 rounded-xl border border-slate-200 bg-white overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-2 text-left">Order</th><th class="px-4 py-2 text-left">Placed</th><th class="px-4 py-2 text-left">Status</th><th class="px-4 py-2 text-right">Total</th></tr></thead>
        <tbody>
            @forelse ($orders as $order)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-2"><a href="{{ route('account.orders.show', $order->order_number) }}" class="font-medium text-slate-800 hover:text-slate-900">{{ $order->order_number }}</a></td>
                    <td class="px-4 py-2">{{ optional($order->placed_at)->toDateString() ?? '—' }}</td>
                    <td class="px-4 py-2">{{ is_object($order->status) ? $order->status->value : $order->status }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format(((int) $order->total_amount) / 100, 2, '.', ',') }} {{ strtoupper($order->currency) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No orders yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
