@php
    $hero = $themeSettings['hero'] ?? [];
    $heading = $hero['heading'] ?? app('current_store')->name;
    $subheading = $hero['subheading'] ?? 'Welcome to our shop. Browse products, add them to your cart, and check out in a few clicks.';
    $ctaLabel = $hero['cta_label'] ?? 'Shop collections';
    $ctaUrl = $hero['cta_url'] ?? route('storefront.collections.index');
@endphp
<div>
    <section class="mx-auto max-w-6xl px-6 py-20 text-center">
        <flux:heading size="2xl">{{ $heading }}</flux:heading>
        <flux:text class="mx-auto mt-3 max-w-2xl">{{ $subheading }}</flux:text>
        <div class="mt-8">
            <flux:button href="{{ $ctaUrl }}" variant="primary">{{ $ctaLabel }}</flux:button>
        </div>
    </section>

    @if (! empty($featuredCollections))
        <section class="mx-auto max-w-6xl px-6 py-12">
            <flux:heading size="lg" class="mb-4">Featured collections</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featuredCollections as $collection)
                    <a wire:key="fc-{{ $collection['id'] }}"
                       href="{{ route('storefront.collections.show', $collection['handle']) }}"
                       class="rounded-lg border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800">
                        <flux:heading size="sm">{{ $collection['title'] }}</flux:heading>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if (! empty($featuredProducts))
        <section class="mx-auto max-w-6xl px-6 py-12">
            <flux:heading size="lg" class="mb-4">Featured products</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featuredProducts as $product)
                    <div wire:key="fp-{{ $product['id'] }}">
                        <x-storefront.product-card :product="$product" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
