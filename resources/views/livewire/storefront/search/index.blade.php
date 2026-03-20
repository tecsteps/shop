<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">
            @if($query)
                Search results for "{{ $query }}"
            @else
                Search
            @endif
        </h1>
        <div class="mt-8">
            @if($query && $results->isEmpty())
                <p class="text-gray-500 dark:text-gray-400">No results found for "{{ $query }}".</p>
            @elseif($results->isNotEmpty())
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
                    @foreach($results as $product)
                        <a href="/products/{{ $product->handle }}" wire:key="sr-{{ $product->id }}" class="group block">
                            <div class="aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
                                @if($product->media && $product->media->isNotEmpty())
                                    <img src="{{ $product->media->first()->url }}" alt="{{ $product->title }}" class="h-full w-full object-cover object-center transition group-hover:opacity-75">
                                @else
                                    <div class="flex h-full items-center justify-center text-gray-400 dark:text-gray-500">
                                        <flux:icon name="photo" class="size-10" />
                                    </div>
                                @endif
                            </div>
                            <h3 class="mt-3 text-sm font-medium text-gray-900 dark:text-white">{{ $product->title }}</h3>
                        </a>
                    @endforeach
                </div>
                @if($results->hasPages())
                    <div class="mt-8">
                        {{ $results->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
