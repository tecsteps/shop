<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => route('storefront.home')], ['label' => 'Collections']]" class="mb-6" />

    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Collections</h1>
    <p class="mt-2 text-gray-600 dark:text-gray-400">Browse our curated collections.</p>

    <div class="mt-10 grid grid-cols-2 gap-6 lg:grid-cols-3">
        <div class="flex aspect-[3/4] items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
            <p class="text-sm">Collections will appear here once the catalog is seeded.</p>
        </div>
    </div>
</div>
