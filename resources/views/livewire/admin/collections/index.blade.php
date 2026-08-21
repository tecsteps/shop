<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Catalog</p><h1 class="mt-2 text-3xl font-bold">Collections</h1><p class="mt-2 text-zinc-600">Group products into curated storefront collections.</p></div>
        <flux:button variant="primary" :href="route('admin.collections.create')" wire:navigate>Create collection</flux:button>
    </div>
    <div class="flex flex-wrap gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search collections" aria-label="Search collections" />
        <flux:select wire:model.live="status" aria-label="Filter collections by status"><option value="all">All statuses</option><option value="active">Active</option><option value="draft">Draft</option><option value="archived">Archived</option></flux:select>
    </div>
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-zinc-200">
        <table class="min-w-full divide-y divide-zinc-200 text-left text-sm"><thead class="bg-zinc-50"><tr><th class="px-5 py-3 font-semibold">Collection</th><th class="px-5 py-3 font-semibold">Status</th><th class="px-5 py-3 font-semibold">Products</th><th class="px-5 py-3"><span class="sr-only">Actions</span></th></tr></thead><tbody class="divide-y divide-zinc-100">
        @forelse($collections as $collection)
            <tr wire:key="collection-{{ $collection->id }}"><td class="px-5 py-4"><a class="font-semibold hover:underline" href="{{ route('admin.collections.edit', $collection) }}" wire:navigate>{{ $collection->title }}</a><div class="text-xs text-zinc-500">/collections/{{ $collection->handle }}</div></td><td class="px-5 py-4"><span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium">{{ ucfirst($collection->status->value) }}</span></td><td class="px-5 py-4">{{ $collection->products_count }}</td><td class="px-5 py-4 text-right"><flux:button size="sm" variant="ghost" wire:click="delete({{ $collection->id }})" wire:confirm="Delete this collection?">Delete</flux:button></td></tr>
        @empty
            <tr><td colspan="4" class="px-5 py-12 text-center text-zinc-500">No collections match your filters.</td></tr>
        @endforelse
        </tbody></table>
    </div>
    <div>{{ $collections->links() }}</div>
</div>
