@extends('storefront.layout')

@section('content')
<section class="overflow-hidden rounded-3xl bg-gradient-to-r from-zinc-900 via-zinc-800 to-zinc-700 px-6 py-14 text-white sm:px-10 sm:py-20">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-200">{{ $store->name }}</p>
    <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-5xl">Shop new arrivals and everyday essentials</h1>
    <p class="mt-4 max-w-2xl text-sm text-zinc-200 sm:text-base">Discover curated collections, fast checkout, and customer account tools that make repeat shopping easy.</p>
    <div class="mt-8 flex flex-wrap gap-3">
        <a href="{{ route('storefront.collections.index') }}" class="rounded-md bg-white px-5 py-3 text-sm font-semibold text-zinc-900 hover:bg-zinc-100">Browse collections</a>
        <a href="{{ route('storefront.search.index') }}" class="rounded-md border border-white/50 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10">Search products</a>
    </div>
</section>

<section class="mt-12">
    <div class="mb-6 flex items-end justify-between gap-4">
        <h2 class="text-2xl font-bold tracking-tight text-zinc-900">Featured Collections</h2>
        <a href="{{ route('storefront.collections.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-900">View all collections</a>
    </div>

    @if ($collections->isEmpty())
        <p class="rounded-lg border border-dashed border-zinc-300 bg-white px-5 py-8 text-sm text-zinc-600">No collections are published yet.</p>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($collections as $collection)
                <a href="{{ route('storefront.collections.show', $collection->handle) }}" class="group rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <h3 class="text-lg font-semibold text-zinc-900">{{ $collection->title }}</h3>
                    <p class="mt-1 text-sm text-zinc-600">{{ $collection->products_count }} products</p>
                    <span class="mt-5 inline-flex text-sm font-medium text-zinc-900 group-hover:underline">Shop now</span>
                </a>
            @endforeach
        </div>
    @endif
</section>

<section class="mt-12">
    <div class="mb-6 flex items-end justify-between gap-4">
        <h2 class="text-2xl font-bold tracking-tight text-zinc-900">Featured Products</h2>
        <a href="{{ route('storefront.search.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-900">View all products</a>
    </div>

    @if ($products->isEmpty())
        <p class="rounded-lg border border-dashed border-zinc-300 bg-white px-5 py-8 text-sm text-zinc-600">No products are available right now.</p>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($products as $product)
                @include('storefront._product-card', ['product' => $product])
            @endforeach
        </div>
    @endif
</section>
@endsection
