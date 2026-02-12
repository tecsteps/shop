@extends('storefront.layout')

@section('content')
<section class="space-y-6">
    <header>
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Collections</h1>
        <p class="mt-2 text-sm text-zinc-600">Browse all product collections available in this store.</p>
    </header>

    @if ($collections->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-12 text-center">
            <h2 class="text-lg font-semibold text-zinc-900">No collections found</h2>
            <p class="mt-2 text-sm text-zinc-600">Check back later for new arrivals.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($collections as $collection)
                <a href="{{ route('storefront.collections.show', $collection->handle) }}" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <h2 class="text-lg font-semibold text-zinc-900">{{ $collection->title }}</h2>
                    @if ($collection->description_html)
                        <p class="mt-2 line-clamp-2 text-sm text-zinc-600">{{ strip_tags($collection->description_html) }}</p>
                    @endif
                    <p class="mt-4 text-xs font-medium uppercase tracking-wide text-zinc-500">{{ $collection->products_count }} products</p>
                </a>
            @endforeach
        </div>

        <div>
            {{ $collections->links() }}
        </div>
    @endif
</section>
@endsection
