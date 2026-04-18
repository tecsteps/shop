<div>
    <section class="mx-auto max-w-6xl px-6 py-20 text-center">
        <flux:heading size="2xl">{{ app('current_store')->name }}</flux:heading>
        <flux:text class="mx-auto mt-3 max-w-2xl">
            Welcome to our shop. Browse products, add them to your cart, and check out in a few clicks.
        </flux:text>
        <div class="mt-8">
            <flux:button href="/collections" variant="primary">Shop collections</flux:button>
        </div>
    </section>
    @if (!empty($featuredCollections ?? []))
        <section class="mx-auto max-w-6xl px-6 py-12">
            <flux:heading size="lg" class="mb-4">Featured</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featuredCollections as $collection)
                    <a href="{{ route('storefront.collections.show', $collection['handle']) }}" class="rounded-lg border p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                        <flux:heading size="sm">{{ $collection['title'] }}</flux:heading>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
