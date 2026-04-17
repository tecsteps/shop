@extends('admin.layout')

@section('title', 'Customers')

@section('content')
<div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr><th class="px-4 py-2 text-left">Name</th><th class="px-4 py-2 text-left">Email</th><th class="px-4 py-2 text-right">Orders</th><th class="px-4 py-2 text-right">Actions</th></tr>
        </thead>
        <tbody>
            @forelse ($customers as $customer)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-2">{{ $customer->name }}</td>
                    <td class="px-4 py-2">{{ $customer->email }}</td>
                    <td class="px-4 py-2 text-right">{{ (int) $customer->orders_count }}</td>
                    <td class="px-4 py-2 text-right"><a href="{{ route('admin.customers.show', $customer->id) }}" class="text-slate-700 hover:text-slate-900">View</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No customers found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $customers->links() }}</div>
@endsection
