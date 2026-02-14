@extends('storefront.layout')

@section('title', ($currentStore->name ?? config('app.name')).' | Collections')

@section('content')
    <section>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Collections</h1>
        <p class="mt-2 text-sm text-slate-600">Explore all active collections available in this store.</p>

        @if ($collections->isEmpty())
            <p class="mt-6 rounded-lg border border-dashed border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-600">
                There are currently no active collections.
            </p>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($collections as $collection)
                    <a href="{{ route('storefront.collections.show', $collection->handle) }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow">
                        <h2 class="font-semibold text-slate-900">{{ $collection->title }}</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            {{ (int) $collection->active_products_count }} active products
                        </p>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $collections->links() }}
            </div>
        @endif
    </section>
@endsection
