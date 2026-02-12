@extends('storefront.layout')

@section('content')
<section class="space-y-6">
    <header class="space-y-3">
        <nav class="text-xs font-medium uppercase tracking-wide text-zinc-500" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-zinc-900">Home</a>
            <span class="mx-2">/</span>
            <a href="{{ route('storefront.collections.index') }}" class="hover:text-zinc-900">Collections</a>
            <span class="mx-2">/</span>
            <span class="text-zinc-700">{{ $collection->title }}</span>
        </nav>

        <h1 class="text-3xl font-bold tracking-tight text-zinc-900">{{ $collection->title }}</h1>

        @if ($collection->description_html)
            <div class="prose prose-sm max-w-3xl text-zinc-700">{!! $collection->description_html !!}</div>
        @endif
    </header>

    <div class="flex flex-wrap items-end justify-between gap-4 rounded-xl border border-zinc-200 bg-white p-4">
        <div>
            <p class="text-sm font-medium text-zinc-700">{{ $productCount }} products</p>
            <p class="text-xs text-zinc-500">Sort and browse items in this collection.</p>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <label for="sort" class="text-sm font-medium text-zinc-700">Sort</label>
            <select id="sort" name="sort" class="rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500" onchange="this.form.submit()">
                <option value="featured" @selected($sort === 'featured')>Featured</option>
                <option value="price_low" @selected($sort === 'price_low')>Price: Low to High</option>
                <option value="price_high" @selected($sort === 'price_high')>Price: High to Low</option>
                <option value="newest" @selected($sort === 'newest')>Newest</option>
                <option value="best_selling" @selected($sort === 'best_selling')>Best Selling</option>
            </select>
        </form>
    </div>

    @if ($products->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-12 text-center">
            <h2 class="text-lg font-semibold text-zinc-900">No products found</h2>
            <p class="mt-2 text-sm text-zinc-600">Try adjusting your sort or browse another collection.</p>
            <a href="{{ route('storefront.collections.index') }}" class="mt-6 inline-flex rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700">Browse all collections</a>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($products as $product)
                @include('storefront._product-card', ['product' => $product])
            @endforeach
        </div>

        <div>
            {{ $products->links() }}
        </div>
    @endif
</section>
@endsection
