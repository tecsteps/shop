<div>
    <x-slot:title>{{ $handle }}</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-2 lg:gap-x-8">
            {{-- Image Gallery --}}
            <div class="aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
                <div class="flex h-full items-center justify-center">
                    <p class="text-gray-400">Product image</p>
                </div>
            </div>

            {{-- Product Info --}}
            <div class="mt-8 lg:mt-0">
                <h1 class="text-3xl font-bold capitalize">{{ str_replace('-', ' ', $handle) }}</h1>

                <div class="mt-4">
                    <span class="text-2xl font-bold">0.00 USD</span>
                </div>

                <p class="mt-4 text-gray-600 dark:text-gray-400">
                    Product description will appear here.
                </p>

                {{-- Variant Selection --}}
                <div class="mt-6 space-y-4">
                    {{-- Variant options will be populated when data is wired up --}}
                </div>

                {{-- Quantity --}}
                <div class="mt-6">
                    <label for="quantity" class="text-sm font-medium">Quantity</label>
                    <div class="mt-1 flex items-center space-x-3">
                        <button class="rounded-lg border border-gray-300 px-3 py-1 dark:border-gray-600">-</button>
                        <span class="w-12 text-center">1</span>
                        <button class="rounded-lg border border-gray-300 px-3 py-1 dark:border-gray-600">+</button>
                    </div>
                </div>

                {{-- Add to Cart --}}
                <button class="mt-8 w-full rounded-lg bg-blue-600 px-8 py-3 text-white hover:bg-blue-700">
                    Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>
