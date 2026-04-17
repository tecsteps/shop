@extends('admin.layout')

@section('title', 'Customer '.$customer->name)

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <h2 class="text-lg font-semibold text-slate-900">Order History</h2>
        <ul class="mt-4 space-y-2 text-sm">
            @forelse ($customer->orders as $order)
                <li class="rounded border border-slate-200 px-3 py-2">
                    <a href="{{ route('admin.orders.show', $order->id) }}" class="font-medium text-slate-800 hover:text-slate-900">{{ $order->order_number }}</a>
                    <span class="ml-2 text-slate-500">{{ number_format(((int) $order->total_amount) / 100, 2, '.', ',') }} {{ strtoupper($order->currency) }}</span>
                </li>
            @empty
                <li class="text-slate-500">No orders yet.</li>
            @endforelse
        </ul>
    </div>
    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-900">Profile</h2>
            <p class="mt-2 text-sm">{{ $customer->name }}</p>
            <p class="text-sm text-slate-500">{{ $customer->email }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-900">Addresses</h2>
            <ul class="mt-2 space-y-2 text-sm text-slate-700">
                @forelse ($customer->addresses as $address)
                    <li class="rounded border border-slate-200 px-2 py-1">{{ $address->label }} @if($address->is_default) <span class="text-xs text-slate-500">(Default)</span>@endif</li>
                @empty
                    <li class="text-slate-500">No addresses.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
