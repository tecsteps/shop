<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront::breadcrumbs :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Collections'],
    ]" />

    <h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">Collections</h1>

    @if ($collections->isEmpty())
        <div class="flex flex-col items-center gap-3 py-24 text-center">
            <svg class="size-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">No collections yet</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Check back soon for new collections.</p>
        </div>
    @else
        <div class="mt-10 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 lg:gap-6">
            @foreach ($collections as $collection)
                <a href="{{ route('storefront.collections.show', ['handle' => $collection->handle]) }}"
                   aria-label="{{ $collection->title }}"
                   class="group relative block aspect-[3/4] overflow-hidden rounded-lg bg-gray-200 transition-transform duration-300 hover:scale-[1.02] focus:outline-hidden focus:ring-2 focus:ring-blue-500 dark:bg-gray-800">
                    <div class="absolute inset-0 bg-gradient-to-br from-gray-300 to-gray-400 dark:from-gray-700 dark:to-gray-800" aria-hidden="true"></div>
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-4 pt-12">
                        <span class="block text-base font-semibold text-white sm:text-lg">{{ $collection->title }}</span>
                        <span class="mt-1 inline-block text-sm text-white/80 underline underline-offset-2 transition group-hover:text-white">Shop now</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
