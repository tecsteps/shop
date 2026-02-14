@extends('admin.layout')

@section('title', $mode === 'create' ? 'Create Product' : 'Edit Product')

@section('content')
@php
    $isEdit = $mode === 'edit' && $product !== null;
    $defaultVariant = null;

    if ($product !== null) {
        $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
    }

    $formAction = '#';

    if ($isEdit && \Illuminate\Support\Facades\Route::has('admin.products.update')) {
        $formAction = route('admin.products.update', ['product' => $product->id]);
    }

    if (! $isEdit && \Illuminate\Support\Facades\Route::has('admin.products.store')) {
        $formAction = route('admin.products.store');
    }

    $publishedAt = old('published_at');

    if ($publishedAt === null && $product?->published_at !== null) {
        $publishedAt = $product->published_at->format('Y-m-d\\TH:i');
    }

    $tags = old('tags');

    if ($tags === null && $product !== null) {
        $tags = implode(', ', is_array($product->tags) ? $product->tags : []);
    }

    $currency = old('currency');

    if ($currency === null) {
        $currency = $defaultVariant?->currency ?? $store->default_currency ?? 'USD';
    }

    $requiresShipping = old('requires_shipping');

    if ($requiresShipping === null) {
        $requiresShipping = $defaultVariant?->requires_shipping ?? true;
    }
@endphp

<div class="rounded-xl border border-slate-200 bg-white p-6">
    <h2 class="text-lg font-semibold text-slate-900">{{ $isEdit ? 'Edit Product' : 'Create Product' }}</h2>
    <p class="mt-2 text-sm text-slate-600">Create and update product data with default variant pricing and inventory.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-medium">Please fix the following errors:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($formAction === '#')
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Product submission route is not wired yet.
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}" class="mt-6 space-y-5">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label for="title" class="block text-sm font-medium text-slate-700">Title</label>
                <input id="title" name="title" type="text" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ old('title', $product?->title) }}">
                @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="handle" class="block text-sm font-medium text-slate-700">Handle</label>
                <input id="handle" name="handle" type="text" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ old('handle', $product?->handle) }}" placeholder="auto-generated-from-title">
                @error('handle')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                <select id="status" name="status" class="mt-1 w-full rounded border border-slate-300 px-3 py-2">
                    @foreach (['draft', 'active', 'archived'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $product ? (is_object($product->status) ? $product->status->value : $product->status) : 'draft') === $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
                @error('status')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="published_at" class="block text-sm font-medium text-slate-700">Published At</label>
                <input id="published_at" name="published_at" type="datetime-local" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ $publishedAt }}">
                @error('published_at')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="vendor" class="block text-sm font-medium text-slate-700">Vendor</label>
                <input id="vendor" name="vendor" type="text" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ old('vendor', $product?->vendor) }}">
                @error('vendor')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="product_type" class="block text-sm font-medium text-slate-700">Product Type</label>
                <input id="product_type" name="product_type" type="text" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ old('product_type', $product?->product_type) }}">
                @error('product_type')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="lg:col-span-2">
                <label for="tags" class="block text-sm font-medium text-slate-700">Tags (comma separated)</label>
                <input id="tags" name="tags" type="text" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ $tags }}" placeholder="new, summer, bestseller">
                @error('tags')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 p-4">
            <h3 class="text-sm font-semibold text-slate-900">Default Variant</h3>
            <p class="mt-1 text-sm text-slate-600">Price and fulfillment values are maintained on the default variant.</p>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div>
                    <label for="price_amount" class="block text-sm font-medium text-slate-700">Price Amount (minor units)</label>
                    <input id="price_amount" name="price_amount" type="number" min="0" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ old('price_amount', $defaultVariant?->price_amount ?? 0) }}">
                    @error('price_amount')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="compare_at_amount" class="block text-sm font-medium text-slate-700">Compare At Amount (minor units)</label>
                    <input id="compare_at_amount" name="compare_at_amount" type="number" min="0" class="mt-1 w-full rounded border border-slate-300 px-3 py-2" value="{{ old('compare_at_amount', $defaultVariant?->compare_at_amount) }}">
                    @error('compare_at_amount')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="currency" class="block text-sm font-medium text-slate-700">Currency</label>
                    <input id="currency" name="currency" type="text" minlength="3" maxlength="3" required class="mt-1 w-full rounded border border-slate-300 px-3 py-2 uppercase" value="{{ $currency }}">
                    @error('currency')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="requires_shipping" value="0">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" name="requires_shipping" value="1" class="rounded border-slate-300" @checked((bool) $requiresShipping)>
                        Requires Shipping
                    </label>
                    @error('requires_shipping')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div>
            <label for="description_html" class="block text-sm font-medium text-slate-700">Description (HTML)</label>
            <textarea id="description_html" name="description_html" rows="8" class="mt-1 w-full rounded border border-slate-300 px-3 py-2 font-mono text-sm">{{ old('description_html', $product?->description_html) }}</textarea>
            @error('description_html')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
            <a href="{{ route('admin.products.index') }}" class="rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back</a>

            <button type="submit" class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-400" @disabled($formAction === '#')>
                {{ $isEdit ? 'Save Product' : 'Create Product' }}
            </button>
        </div>
    </form>

    @if ($isEdit && \Illuminate\Support\Facades\Route::has('admin.products.destroy'))
        <form method="POST" action="{{ route('admin.products.destroy', ['product' => $product->id]) }}" class="mt-4" onsubmit="return confirm('Archive this product?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                Archive Product
            </button>
        </form>
    @endif
</div>
@endsection
