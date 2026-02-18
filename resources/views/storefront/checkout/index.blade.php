<x-layouts::storefront>
    <x-slot:title>Checkout</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold">Checkout</h1>
        <div class="mt-8 lg:grid lg:grid-cols-12 lg:gap-x-12">
            <div class="lg:col-span-7">
                <div class="rounded-lg border border-gray-200 p-8 text-center dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">Checkout will be available when cart and payment services are implemented.</p>
                </div>
            </div>
            <div class="mt-8 lg:col-span-5 lg:mt-0">
                <x-storefront.order-summary />
            </div>
        </div>
    </div>
</x-layouts::storefront>
