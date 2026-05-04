<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[['label' => 'Collections']]" />

    <div class="mt-6 flex flex-col gap-3">
        <h1 class="text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">Collections</h1>
        <p class="max-w-2xl text-zinc-600 dark:text-zinc-400">Browse curated groups of products from this store.</p>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($collections as $collection)
            <a href="{{ route('collections.show', $collection->handle) }}" class="group rounded-lg border border-zinc-200 bg-white p-5 transition hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700" wire:navigate wire:key="collection-index-{{ $collection->getKey() }}">
                <div class="flex aspect-[4/3] items-center justify-center rounded-md bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                    <flux:icon name="rectangle-stack" class="size-10" />
                </div>
                <h2 class="mt-4 text-lg font-semibold text-zinc-950 group-hover:underline dark:text-white">{{ $collection->title }}</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $collection->products_count }} products</p>
            </a>
        @endforeach
    </div>
</section>
