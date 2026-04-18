<div class="mx-auto max-w-6xl px-6 py-12">
    <x-storefront.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Cart'],
    ]" />

    <flux:heading size="xl" class="mb-6">Your cart</flux:heading>

    <flux:callout icon="shopping-cart">
        Your cart is empty. Browse <a href="{{ route('storefront.collections.index') }}" class="font-medium underline">our collections</a> to get started.
    </flux:callout>

    {{-- Phase 4 will wire the live cart contents and totals here. --}}
</div>
