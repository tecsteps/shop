@extends('admin.layout')

@section('title', 'Analytics')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">Analytics</h1>
            <p class="mt-1 text-sm text-zinc-600">KPI summary and top products for the selected date range.</p>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-2 rounded-lg border border-zinc-200 bg-white p-3">
            <label class="block text-xs text-zinc-600">
                <span class="mb-1 block">Start date</span>
                <input type="date" name="start_date" value="{{ $startDate }}" class="rounded-md border border-zinc-300 px-2 py-1.5 text-sm">
            </label>

            <label class="block text-xs text-zinc-600">
                <span class="mb-1 block">End date</span>
                <input type="date" name="end_date" value="{{ $endDate }}" class="rounded-md border border-zinc-300 px-2 py-1.5 text-sm">
            </label>

            <button type="submit" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
                Apply
            </button>
        </form>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4">
            <p class="text-sm text-zinc-600">Total Sales</p>
            <p class="mt-2 text-2xl font-semibold">{{ strtoupper($currency) }} {{ number_format($totalSales / 100, 2) }}</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4">
            <p class="text-sm text-zinc-600">Orders</p>
            <p class="mt-2 text-2xl font-semibold">{{ number_format($ordersCount) }}</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4">
            <p class="text-sm text-zinc-600">Average Order Value</p>
            <p class="mt-2 text-2xl font-semibold">{{ strtoupper($currency) }} {{ number_format($averageOrderValue / 100, 2) }}</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4">
            <p class="text-sm text-zinc-600">Units Sold</p>
            <p class="mt-2 text-2xl font-semibold">{{ number_format($unitsSold) }}</p>
        </div>
    </div>

    <div class="mt-8 rounded-lg border border-zinc-200 bg-white">
        <div class="border-b border-zinc-200 px-4 py-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-700">Top Products</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">Units Sold</th>
                        <th class="px-4 py-3 text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse ($topProducts as $product)
                        <tr>
                            <td class="px-4 py-3">{{ $product->title }}</td>
                            <td class="px-4 py-3">{{ number_format((int) $product->units_sold) }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ strtoupper($currency) }} {{ number_format(((int) $product->revenue_amount) / 100, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-zinc-500">No order data available for this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
