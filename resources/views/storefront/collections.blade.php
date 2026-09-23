
@section('title', 'Collections')
<div>
<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        @include('storefront.components.breadcrumbs', ['items' => [['label' => 'Home', 'url' => '/'], ['label' => 'Collections']]])
        <div class="mt-7 max-w-2xl"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-800 dark:text-blue-300">Find your next favorite</p><h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Collections</h1><p class="mt-3 text-sm leading-6 text-stone-600 dark:text-stone-300">Browse the latest collections from {{ data_get($currentStore ?? null, 'name', 'our store') }}.</p></div>
        @if ($collections->isNotEmpty())
            <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
                @foreach ($collections as $collection)
                    <a href="{{ route('storefront.collection', ['handle' => $collection->handle]) }}" class="group overflow-hidden rounded-2xl border border-stone-200 bg-white transition hover:-translate-y-0.5 hover:shadow-lg dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex aspect-[4/5] items-center justify-center bg-stone-100 text-stone-400 dark:bg-stone-800">@include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-12'])</div>
                        <div class="p-4"><h2 class="font-semibold group-hover:underline">{{ $collection->title }}</h2><p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ $collection->products_count }} {{ \Illuminate\Support\Str::plural('product', $collection->products_count) }}</p></div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $collections->links() }}</div>
        @else
            <p class="mt-8 rounded-2xl border border-dashed border-stone-300 px-6 py-12 text-center text-sm text-stone-600 dark:border-stone-700 dark:text-stone-300">Collections are coming soon.</p>
        @endif
    </section>
</div>
