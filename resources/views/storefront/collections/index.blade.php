<x-storefront.layout :title="'Collections'">
    <section class="mx-auto max-w-7xl px-4 py-10">
        <h1 class="text-3xl font-bold tracking-normal">Collections</h1>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            @foreach($collections as $collection)
                <a href="{{ route('collections.show', $collection->handle) }}" class="rounded-lg border border-zinc-200 p-5 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900">
                    <h2 class="font-semibold">{{ $collection->title }}</h2>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $collection->products_count }} products</p>
                </a>
            @endforeach
        </div>
    </section>
</x-storefront.layout>

