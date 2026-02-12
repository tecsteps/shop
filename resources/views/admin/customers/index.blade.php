@extends('admin.layout')

@section('title', 'Customers')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900">Customers</h1>
        <p class="mt-1 text-sm text-zinc-600">View customer profiles and spending history.</p>
    </div>

    <form method="GET" class="mb-4 rounded-lg border border-zinc-200 bg-white p-4">
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            placeholder="Search by name or email..."
            class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
        >
    </form>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Orders</th>
                        <th class="px-4 py-3">Total spent</th>
                        <th class="px-4 py-3">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-zinc-900 hover:underline">
                                    {{ $customer->name ?: 'Unnamed customer' }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $customer->email }}</td>
                            <td class="px-4 py-3">{{ $customer->orders_count }}</td>
                            <td class="px-4 py-3">
                                {{ strtoupper($currency) }} {{ number_format(((int) $customer->orders_sum_total_amount) / 100, 2) }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600">{{ optional($customer->created_at)->format('M j, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-zinc-500">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3">
            {{ $customers->links() }}
        </div>
    </div>
@endsection
