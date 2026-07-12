<div class="grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-5 md:grid-cols-3 lg:grid-cols-4" aria-live="polite">
    @forelse ($products as $product)
        <x-storefront.product-card :product="$product" />
    @empty
        <p class="col-span-full py-12 text-center text-slate-500">Products are coming soon.</p>
    @endforelse
</div>
