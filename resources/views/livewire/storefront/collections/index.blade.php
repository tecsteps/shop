<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Collections', 'url' => null],
    ]" />

    <h1 class="mt-4 text-3xl font-bold text-zinc-900 dark:text-white">Collections</h1>

    <div class="mt-8 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($collections as $collection)
            <a href="{{ route('storefront.collections.show', $collection->handle) }}" wire:navigate wire:key="collection-{{ $collection->id }}" class="group">
                <div class="aspect-3/4 overflow-hidden rounded-xl bg-zinc-100 transition-transform group-hover:scale-[1.02] dark:bg-zinc-800"></div>
                <p class="mt-2 font-medium text-zinc-900 dark:text-white">{{ $collection->title }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-10">
        {{ $collections->links() }}
    </div>
</div>
