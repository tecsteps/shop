<div class="mx-auto max-w-6xl px-6 py-12">
    <x-storefront.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Collections', 'url' => route('storefront.collections.index')],
        ['label' => $collection->title],
    ]" />

    <flux:heading size="xl" class="mb-2">{{ $collection->title }}</flux:heading>
    @if ($collection->description_html)
        <div class="prose prose-zinc mb-6 max-w-none dark:prose-invert">{!! $collection->description_html !!}</div>
    @endif

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search products..." class="w-full sm:w-64" />
        <flux:select wire:model.live="sort" class="w-full sm:w-48">
            <flux:select.option value="default">Sort: default</flux:select.option>
            <flux:select.option value="title-asc">Title A-Z</flux:select.option>
            <flux:select.option value="title-desc">Title Z-A</flux:select.option>
            <flux:select.option value="newest">Newest</flux:select.option>
        </flux:select>
    </div>

    @if ($products->isEmpty())
        <flux:callout icon="information-circle">No products found.</flux:callout>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($products as $product)
                <div wire:key="p-{{ $product->id }}">
                    <x-storefront.product-card :product="$product" />
                </div>
            @endforeach
        </div>

        <x-storefront.pagination :paginator="$products" class="mt-8" />
    @endif
</div>
