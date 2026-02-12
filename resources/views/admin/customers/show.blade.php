@extends('admin.layout')

@section('title', 'Customer')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900">{{ $customer->name ?: 'Customer' }}</h1>
        <p class="mt-1 text-sm text-zinc-600">{{ $customer->email }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-lg border border-zinc-200 bg-white p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Profile</h2>

                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-zinc-500">Name</dt>
                        <dd class="font-medium text-zinc-900">{{ $customer->name ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Email</dt>
                        <dd class="font-medium text-zinc-900">{{ $customer->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Marketing</dt>
                        <dd class="font-medium text-zinc-900">{{ $customer->marketing_opt_in ? 'Opted in' : 'Not subscribed' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">Created</dt>
                        <dd class="font-medium text-zinc-900">{{ optional($customer->created_at)->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>
            </section>

            <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-4 py-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Order History</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm">
                        <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                            <tr>
                                <th class="px-4 py-3">Order</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 bg-white">
                            @forelse ($orders as $order)
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-zinc-900 hover:underline">
                                            {{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">{{ optional($order->placed_at)->format('M j, Y') }}</td>
                                    <td class="px-4 py-3">{{ $order->status->value }}</td>
                                    <td class="px-4 py-3 text-right">{{ strtoupper($order->currency) }} {{ number_format($order->total_amount / 100, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-zinc-500">No orders for this customer.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-zinc-200 px-4 py-3">
                    {{ $orders->links() }}
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="rounded-lg border border-zinc-200 bg-white p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Addresses</h2>

                <div class="mt-3 space-y-3 text-sm">
                    @forelse ($customer->addresses as $address)
                        <div class="rounded-md border border-zinc-200 px-3 py-2">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-medium text-zinc-900">{{ $address->label ?: 'Address' }}</p>
                                @if ($address->is_default)
                                    <span class="rounded bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700">Default</span>
                                @endif
                            </div>

                            @if (is_array($address->address_json))
                                <div class="mt-1 text-xs text-zinc-600">
                                    @foreach ($address->address_json as $value)
                                        @if (! empty($value))
                                            <p>{{ $value }}</p>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-zinc-500">No addresses available.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
