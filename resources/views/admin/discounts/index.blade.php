@extends('admin.layout')

@section('title', 'Discounts')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">Discounts</h1>
            <p class="mt-1 text-sm text-zinc-600">Create and manage promotional rules.</p>
        </div>

        <a href="{{ route('admin.discounts.create') }}" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
            Create Discount
        </a>
    </div>

    <form method="GET" class="mb-4 grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 md:grid-cols-[1fr_220px_auto]">
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            placeholder="Search by code..."
            class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
        >

        <select name="status" class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $statusOption)
                <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>
                    {{ ucfirst($statusOption->value) }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
            Filter
        </button>
    </form>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Value</th>
                        <th class="px-4 py-3">Usage</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Dates</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse ($discounts as $discount)
                        <tr>
                            <td class="px-4 py-3 font-medium text-zinc-900">{{ $discount->code ?: 'Automatic' }}</td>
                            <td class="px-4 py-3">{{ $discount->type->value }}</td>
                            <td class="px-4 py-3">
                                @if ($discount->value_type === \App\Enums\DiscountValueType::Percent)
                                    {{ $discount->value_amount }}%
                                @elseif ($discount->value_type === \App\Enums\DiscountValueType::Fixed)
                                    {{ strtoupper($currency) }} {{ number_format($discount->value_amount / 100, 2) }}
                                @else
                                    Free shipping
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $discount->usage_count }} / {{ $discount->usage_limit ?? 'unlimited' }}</td>
                            <td class="px-4 py-3">{{ $discount->status->value }}</td>
                            <td class="px-4 py-3 text-zinc-600">
                                {{ optional($discount->starts_at)->format('M j, Y') }}
                                @if ($discount->ends_at)
                                    - {{ $discount->ends_at->format('M j, Y') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.discounts.edit', $discount) }}" class="text-sm font-medium text-zinc-900 hover:underline">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-zinc-500">No discounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3">
            {{ $discounts->links() }}
        </div>
    </div>
@endsection
