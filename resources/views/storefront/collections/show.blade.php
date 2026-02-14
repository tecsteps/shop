@extends('storefront.layout')

@section('title', $collection->title.' | '.($currentStore->name ?? config('app.name')))

@section('content')
    <section>
        <a href="{{ route('storefront.collections.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Back to collections</a>

        <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">{{ $collection->title }}</h1>

        @if (is_string($collection->description_html) && $collection->description_html !== '')
            <div class="prose prose-slate mt-4 max-w-none text-sm">
                {!! $collection->description_html !!}
            </div>
        @endif
    </section>

    <section class="mt-8">
        <h2 class="text-lg font-semibold text-slate-900">Products</h2>

        @if ($collection->products->isEmpty())
            <p class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-600">
                No active products are assigned to this collection yet.
            </p>
        @else
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($collection->products as $product)
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
