@extends('admin.layout')

@section('title', 'Products')

@section('content')
<div class="mb-4 flex justify-end">
    <a href="{{ route('admin.products.create') }}" class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">New Product</a>
</div>

<div class="rounded-xl border border-slate-200 bg-white overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="px-4 py-2 text-left">Title</th>
                <th class="px-4 py-2 text-left">Handle</th>
                <th class="px-4 py-2 text-left">Status</th>
                <th class="px-4 py-2 text-right">Variants</th>
                <th class="px-4 py-2 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-2">{{ $product->title }}</td>
                    <td class="px-4 py-2 font-mono text-xs">{{ $product->handle }}</td>
                    <td class="px-4 py-2">{{ is_object($product->status) ? $product->status->value : $product->status }}</td>
                    <td class="px-4 py-2 text-right">{{ (int) $product->variants_count }}</td>
                    <td class="px-4 py-2 text-right">
                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="text-slate-700 hover:text-slate-900">Edit</a>

                            @if (\Illuminate\Support\Facades\Route::has('admin.products.destroy'))
                                <form method="POST" action="{{ route('admin.products.destroy', ['product' => $product->id]) }}" onsubmit="return confirm('Archive this product?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-700 hover:text-rose-900">Archive</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No products found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $products->links() }}</div>
@endsection
