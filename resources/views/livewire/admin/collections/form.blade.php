<section class="space-y-6 pb-24">
    <div>
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.collections.index')" wire:navigate>Collections</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $isEditing ? $title : 'Add collection' }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <flux:heading size="xl" class="mt-3">{{ $isEditing ? $title : 'Add collection' }}</flux:heading>
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="space-y-5">
                    <flux:input wire:model.live.debounce.300ms="title" label="Title" placeholder="Summer Collection" />
                    <flux:error name="title" />

                    <flux:input wire:model="handle" label="Handle" placeholder="summer-collection" />
                    <flux:error name="handle" />

                    <flux:textarea wire:model="descriptionHtml" label="Description" rows="6" placeholder="Describe this collection..." />
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Products</flux:heading>

                <div class="mt-5 space-y-4">
                    <flux:input wire:model.live.debounce.300ms="productSearch" icon="magnifying-glass" label="Search products" placeholder="Search products..." />

                    @if ($searchResults->isNotEmpty())
                        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                            @foreach ($searchResults as $product)
                                <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-3 py-2 last:border-b-0 dark:border-zinc-700" wire:key="collection-search-result-{{ $product->getKey() }}">
                                    <div>
                                        <div class="font-medium">{{ $product->title }}</div>
                                        <div class="text-xs text-zinc-500">/{{ $product->handle }}</div>
                                    </div>
                                    <flux:button type="button" wire:click="addProduct({{ $product->getKey() }})" variant="ghost" icon="plus">Add</flux:button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="space-y-3">
                        <flux:text>Assigned products</flux:text>

                        @forelse ($assignedProducts as $product)
                            <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700" wire:key="collection-assigned-product-{{ $product->getKey() }}">
                                <div class="flex items-center gap-3">
                                    <div class="flex size-9 items-center justify-center rounded-md bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                        <flux:icon name="cube" class="size-4" />
                                    </div>
                                    <div>
                                        <div class="font-medium">{{ $product->title }}</div>
                                        <div class="text-xs text-zinc-500">/{{ $product->handle }}</div>
                                    </div>
                                </div>

                                <flux:button type="button" wire:click="removeProduct({{ $product->getKey() }})" variant="ghost" icon="x-mark">Remove</flux:button>
                            </div>
                        @empty
                            <flux:text>No products assigned.</flux:text>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <aside>
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:select wire:model="status" label="Status">
                    <flux:select.option value="draft">Draft</flux:select.option>
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="archived">Archived</flux:select.option>
                </flux:select>
            </div>
        </aside>

        <div class="fixed bottom-0 left-0 right-0 z-40 border-t border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur dark:border-zinc-700 dark:bg-zinc-950/95 lg:left-64">
            <div class="mx-auto flex max-w-7xl justify-end gap-3">
                <flux:button :href="route('admin.collections.index')" wire:navigate variant="ghost">Discard</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
            </div>
        </div>
    </form>
</section>
