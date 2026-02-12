@extends('storefront.layout')

@section('content')
<section class="space-y-6">
    <header>
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Search</h1>
        <p class="mt-2 text-sm text-zinc-600">Find products, collections, and pages.</p>
    </header>

    <form method="GET" action="{{ route('storefront.search.index') }}" class="rounded-xl border border-zinc-200 bg-white p-4">
        <label for="q" class="block text-sm font-medium text-zinc-700">Search query</label>
        <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center">
            <input id="q" type="search" name="q" value="{{ $query }}" placeholder="Search products" class="w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">

            <div class="flex gap-2">
                <label for="sort" class="sr-only">Sort results</label>
                <select id="sort" name="sort" class="rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500">
                    <option value="relevance" @selected($sort === 'relevance')>Relevance</option>
                    <option value="price_low" @selected($sort === 'price_low')>Price: Low to High</option>
                    <option value="price_high" @selected($sort === 'price_high')>Price: High to Low</option>
                    <option value="newest" @selected($sort === 'newest')>Newest</option>
                </select>
                <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700">Search</button>
            </div>
        </div>
    </form>

    @if ($query !== '' && ($collections->isNotEmpty() || $pages->isNotEmpty()))
        <div class="grid gap-4 lg:grid-cols-2">
            @if ($collections->isNotEmpty())
                <section class="rounded-xl border border-zinc-200 bg-white p-4">
                    <h2 class="text-lg font-semibold text-zinc-900">Matching collections</h2>
                    <ul class="mt-3 space-y-2 text-sm text-zinc-700">
                        @foreach ($collections as $collection)
                            <li>
                                <a href="{{ route('storefront.collections.show', $collection->handle) }}" class="hover:underline">{{ $collection->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($pages->isNotEmpty())
                <section class="rounded-xl border border-zinc-200 bg-white p-4">
                    <h2 class="text-lg font-semibold text-zinc-900">Matching pages</h2>
                    <ul class="mt-3 space-y-2 text-sm text-zinc-700">
                        @foreach ($pages as $page)
                            <li>
                                <a href="{{ route('storefront.pages.show', $page->handle) }}" class="hover:underline">{{ $page->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    @endif

    @if ($products->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-12 text-center">
            <h2 class="text-lg font-semibold text-zinc-900">No results found</h2>
            <p class="mt-2 text-sm text-zinc-600">
                @if ($query !== '')
                    No results found for "{{ $query }}". Try a different search term.
                @else
                    Enter a search term to start browsing products.
                @endif
            </p>
        </div>
    @else
        <section>
            <h2 class="text-lg font-semibold text-zinc-900">Products</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    @include('storefront._product-card', ['product' => $product])
                @endforeach
            </div>
        </section>

        <div>
            {{ $products->links() }}
        </div>
    @endif
</section>
@endsection
