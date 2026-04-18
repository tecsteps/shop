<div class="mx-auto max-w-6xl px-6 py-12">
    <x-storefront.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('storefront.home')],
        ['label' => 'Collections'],
    ]" />

    <flux:heading size="xl" class="mb-6">Collections</flux:heading>

    @if (empty($collections))
        <flux:callout icon="information-circle">No collections yet. Check back soon.</flux:callout>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($collections as $collection)
                <a wire:key="col-{{ $collection->id }}"
                   href="{{ route('storefront.collections.show', $collection->handle) }}"
                   class="rounded-lg border border-zinc-200 p-6 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800">
                    <flux:heading size="md">{{ $collection->title }}</flux:heading>
                </a>
            @endforeach
        </div>
    @endif
</div>
