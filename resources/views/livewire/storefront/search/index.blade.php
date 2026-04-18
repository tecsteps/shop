<div class="mx-auto max-w-6xl px-6 py-12">
    <x-storefront.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Search'],
    ]" />

    <flux:heading size="xl" class="mb-6">Search</flux:heading>

    <div class="mb-8 max-w-xl">
        <flux:input wire:model.live.debounce.300ms="query" placeholder="What are you looking for?" autofocus />
    </div>

    @if (trim($query) === '')
        <flux:text>Type a query to search for products.</flux:text>
    @elseif ($results->isEmpty())
        <flux:callout icon="magnifying-glass">No products matched "{{ $query }}".</flux:callout>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($results as $product)
                <div wire:key="sr-{{ $product->id }}">
                    <x-storefront.product-card :product="$product" />
                </div>
            @endforeach
        </div>

        <x-storefront.pagination :paginator="$results" class="mt-8" />
    @endif
</div>
