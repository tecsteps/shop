@extends('storefront.layout')

@section('title', 'Account Dashboard')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Welcome, {{ $customer->name }}</h1>
        <p class="mt-1 text-sm text-slate-600">Manage your profile, orders, and addresses.</p>
    </div>
    <form method="POST" action="{{ route('account.logout') }}">
        @csrf
        <button class="rounded border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100" type="submit">Log out</button>
    </form>
</div>

<div class="mt-8 grid gap-4 sm:grid-cols-3">
    <a href="{{ route('account.orders.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-slate-300">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Orders</h2>
        <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $recentOrders->count() }}</p>
    </a>
    <a href="{{ route('account.addresses.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-slate-300">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Addresses</h2>
        <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $addressCount }}</p>
    </a>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Email</h2>
        <p class="mt-3 text-sm text-slate-800">{{ $customer->email }}</p>
    </div>
</div>
@endsection
