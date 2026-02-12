@extends('admin.layout')

@section('title', 'Edit Product')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">Edit Product</h1>
            <p class="mt-1 text-sm text-zinc-600">{{ $product->title }}</p>
        </div>

        @if ($product->status === \App\Enums\ProductStatus::Draft)
            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this draft product?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-md border border-rose-300 bg-white px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                    Delete Draft
                </button>
            </form>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.products._form', ['product' => $product])

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.products.index') }}" class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
                Back
            </a>
            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700">
                Save Changes
            </button>
        </div>
    </form>
@endsection
