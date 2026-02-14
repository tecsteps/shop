@extends('storefront.layout')

@section('title', ($currentStore->name ?? config('app.name')).' | Home')

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Storefront</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
            {{ $currentStore->name ?? config('app.name') }}
        </h1>
        <p class="mt-3 max-w-2xl text-sm text-slate-600">
            Browse current collections and recently published products.
        </p>
    </section>

    <section class="mt-10">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Featured Collections</h2>
            <a href="{{ route('storefront.collections.index') }}" class="text-sm font-medium text-slate-700 hover:text-slate-900">
                View all
            </a>
        </div>

        @if ($collections->isEmpty())
            <p class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-600">
                No active collections are available yet.
            </p>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($collections as $collection)
                    <a href="{{ route('storefront.collections.show', $collection->handle) }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow">
                        <h3 class="font-semibold text-slate-900">{{ $collection->title }}</h3>
                        <p class="mt-2 text-sm text-slate-600">
                            {{ (int) $collection->products_count }} products
                        </p>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-10">
        <h2 class="text-xl font-semibold text-slate-900">New Products</h2>

        @if ($products->isEmpty())
            <p class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-600">
                No products have been published yet.
            </p>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($products as $product)
                    @php($variant = $product->variants->first())
                    <a href="{{ route('storefront.products.show', $product->handle) }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow">
                        <h3 class="font-semibold text-slate-900">{{ $product->title }}</h3>
                        @if (is_string($product->vendor) && $product->vendor !== '')
                            <p class="mt-1 text-xs uppercase tracking-wide text-slate-500">{{ $product->vendor }}</p>
                        @endif
                        <p class="mt-3 text-sm font-medium text-slate-700">
                            @if ($variant)
                                {{ number_format(((int) $variant->price_amount) / 100, 2, '.', ',') }} {{ strtoupper((string) $variant->currency) }}
                            @else
                                Price unavailable
                            @endif
                        </p>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endsection
