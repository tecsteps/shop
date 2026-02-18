@props(['title' => '', 'handle' => '', 'price' => 0, 'currency' => 'USD', 'imageUrl' => null])

<a href="/products/{{ $handle }}" class="group block">
    <div class="aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $title }}"
                 class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                 loading="lazy">
        @else
            <div class="flex h-full items-center justify-center">
                <span class="text-gray-400">No image</span>
            </div>
        @endif
    </div>
    <div class="mt-3">
        <h3 class="text-sm font-medium text-gray-900 group-hover:text-blue-600 dark:text-gray-100 dark:group-hover:text-blue-400">
            {{ $title }}
        </h3>
        <x-storefront.price :amount="$price" :currency="$currency" class="mt-1 text-sm text-gray-700 dark:text-gray-300" />
    </div>
</a>
