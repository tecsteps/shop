@extends('admin.layout')

@section('title', 'Inventory')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900">Inventory</h1>
        <p class="mt-1 text-sm text-zinc-600">Track stock by variant and update quantities.</p>
    </div>

    <form method="GET" class="mb-4 grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 md:grid-cols-[1fr_220px_auto]">
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            placeholder="Search by product or SKU..."
            class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
        >

        <select name="stock" class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
            <option value="all" @selected($stock === 'all')>All stock</option>
            <option value="in_stock" @selected($stock === 'in_stock')>In stock (&gt; 5)</option>
            <option value="low_stock" @selected($stock === 'low_stock')>Low stock (1-5)</option>
            <option value="out_of_stock" @selected($stock === 'out_of_stock')>Out of stock (&lt;= 0)</option>
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
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">Variant</th>
                        <th class="px-4 py-3">On hand</th>
                        <th class="px-4 py-3">Reserved</th>
                        <th class="px-4 py-3">Available</th>
                        <th class="px-4 py-3">Policy</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse ($inventoryItems as $item)
                        @php
                            $variant = $item->variant;
                            $product = $variant?->product;
                            $available = $item->quantity_on_hand - $item->quantity_reserved;
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900">{{ $product?->title ?? 'Deleted product' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900">{{ $variant?->sku ?? 'No SKU' }}</div>
                                <div class="text-xs text-zinc-500">Variant #{{ $variant?->id }}</div>
                            </td>
                            <td class="px-4 py-3" colspan="4">
                                <form method="POST" action="{{ route('admin.inventory.update', $item) }}" class="grid gap-2 sm:grid-cols-[140px_120px_120px_auto] sm:items-center">
                                    @csrf
                                    @method('PUT')

                                    <input
                                        type="number"
                                        min="0"
                                        name="quantity_on_hand"
                                        value="{{ old('quantity_on_hand', $item->quantity_on_hand) }}"
                                        class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                                    >

                                    <div class="text-sm text-zinc-600">{{ $item->quantity_reserved }}</div>

                                    <div class="text-sm font-medium text-zinc-900">{{ $available }}</div>

                                    <div class="flex items-center gap-2">
                                        <select name="policy" class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
                                            @foreach ($policies as $policy)
                                                <option value="{{ $policy->value }}" @selected($item->policy->value === $policy->value)>
                                                    {{ $policy->value }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <button type="submit" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
                                            Save
                                        </button>
                                    </div>
                                </form>
                            </td>
                            <td class="px-4 py-3"></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-zinc-500">No inventory items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3">
            {{ $inventoryItems->links() }}
        </div>
    </div>
@endsection
