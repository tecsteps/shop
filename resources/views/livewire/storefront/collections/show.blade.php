<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white lg:text-3xl">{{ $collection->title }}</h1>
        @if($collection->description_html)
            <div class="prose prose-sm mt-4 dark:prose-invert">
                {!! $collection->description_html !!}
            </div>
        @endif
        <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 lg:gap-6">
            @foreach($products as $product)
                <a href="/products/{{ $product->handle }}" wire:key="cp-{{ $product->id }}" class="group block">
                    <div class="aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
                        @if($product->media->isNotEmpty())
                            <img src="{{ $product->media->first()->url }}" alt="{{ $product->title }}" class="h-full w-full object-cover object-center transition group-hover:opacity-75">
                        @else
                            <div class="flex h-full items-center justify-center text-gray-400 dark:text-gray-500">
                                <flux:icon name="photo" class="size-10" />
                            </div>
                        @endif
                    </div>
                    <h3 class="mt-3 text-sm font-medium text-gray-900 dark:text-white">{{ $product->title }}</h3>
                    @php $variant = $product->variants->first(); @endphp
                    @if($variant)
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">${{ number_format($variant->price_amount / 100, 2) }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>
