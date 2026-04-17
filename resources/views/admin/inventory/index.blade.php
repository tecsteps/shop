@extends('admin.layout')

@section('title', 'Inventory')

@section('content')
<div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="px-4 py-2 text-left">Product</th>
                <th class="px-4 py-2 text-left">Variant</th>
                <th class="px-4 py-2 text-right">On Hand</th>
                <th class="px-4 py-2 text-right">Reserved</th>
                <th class="px-4 py-2 text-right">Available</th>
                <th class="px-4 py-2 text-left">Policy</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                @php($available = (int) $item->quantity_on_hand - (int) $item->quantity_reserved)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-2">{{ $item->variant?->product?->title ?? 'Unknown' }}</td>
                    <td class="px-4 py-2">{{ $item->variant?->sku ?? ('Variant #'.$item->variant_id) }}</td>
                    <td class="px-4 py-2 text-right">{{ (int) $item->quantity_on_hand }}</td>
                    <td class="px-4 py-2 text-right">{{ (int) $item->quantity_reserved }}</td>
                    <td class="px-4 py-2 text-right">{{ $available }}</td>
                    <td class="px-4 py-2">{{ is_object($item->policy) ? $item->policy->value : $item->policy }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No inventory records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $items->links() }}</div>
@endsection
