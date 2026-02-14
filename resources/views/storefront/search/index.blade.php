@extends('storefront.layout')

@section('title', 'Search | '.($currentStore->name ?? config('app.name')))

@section('content')
    <section>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Search Products</h1>

        <form method="GET" action="{{ route('storefront.search.index') }}" class="mt-4 flex w-full max-w-xl gap-2">
            <label for="q" class="sr-only">Search query</label>
            <input
                id="q"
                name="q"
                type="search"
                value="{{ $query }}"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none"
                placeholder="Search by title, handle, vendor, or product type"
            >
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                Search
            </button>
        </form>

        @if ($query !== '')
            <p class="mt-3 text-sm text-slate-600">
                {{ $products->total() }} result{{ $products->total() === 1 ? '' : 's' }} for "{{ $query }}"
            </p>
        @endif
    </section>

    <section class="mt-6">
        @if ($products->isEmpty())
            <p class="rounded-lg border border-dashed border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-600">
                No products matched your search.
            </p>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($products as $product)
                    @php($variant = $product->variants->first())
                    <a href="{{ route('storefront.products.show', $product->handle) }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow">
                        <h2 class="font-semibold text-slate-900">{{ $product->title }}</h2>
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

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    </section>
@endsection
