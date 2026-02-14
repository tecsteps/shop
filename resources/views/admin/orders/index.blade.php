@extends('admin.layout')

@section('title', 'Orders')

@section('content')
<div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="px-4 py-2 text-left">Order</th>
                <th class="px-4 py-2 text-left">Email</th>
                <th class="px-4 py-2 text-left">Financial</th>
                <th class="px-4 py-2 text-left">Fulfillment</th>
                <th class="px-4 py-2 text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-2"><a class="text-slate-700 hover:text-slate-900" href="{{ route('admin.orders.show', $order->id) }}">{{ $order->order_number }}</a></td>
                    <td class="px-4 py-2">{{ $order->email ?? 'Guest' }}</td>
                    <td class="px-4 py-2">{{ is_object($order->financial_status) ? $order->financial_status->value : $order->financial_status }}</td>
                    <td class="px-4 py-2">{{ is_object($order->fulfillment_status) ? $order->fulfillment_status->value : $order->fulfillment_status }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format(((int) $order->total_amount) / 100, 2, '.', ',') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No orders found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
