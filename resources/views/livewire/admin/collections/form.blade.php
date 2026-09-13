<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.collections.index') }}" wire:navigate>Collections</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $isEditing ? $this->collection->title : 'Add collection' }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" class="mb-6">{{ $isEditing ? $this->collection->title : 'Add collection' }}</flux:heading>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="flex flex-col gap-4 lg:col-span-2">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="title" placeholder="Summer Collection" />
                    <flux:error name="title" />
                </flux:field>
                <flux:field class="mt-4">
                    <flux:label>Handle</flux:label>
                    <flux:input wire:model="handle" placeholder="summer-collection" />
                    <flux:error name="handle" />
                </flux:field>
                <flux:field class="mt-4">
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="descriptionHtml" rows="5" />
                    <flux:error name="descriptionHtml" />
                </flux:field>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Products</flux:heading>
                <flux:input wire:model.live.debounce.300ms="productSearch" icon="magnifying-glass" placeholder="Search products..." aria-label="Search products" class="mt-4" />
                @if ($this->productSearch !== '')
                    <div class="mt-2 max-h-56 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        @forelse ($searchResults as $result)
                            <div class="flex items-center justify-between gap-2 border-b border-zinc-100 px-3 py-2 last:border-0 dark:border-zinc-800" wire:key="result-{{ $result->getKey() }}">
                                <span class="text-sm">{{ $result->title }}</span>
                                <flux:button variant="ghost" size="sm" wire:click="addProduct({{ $result->getKey() }})">Add</flux:button>
                            </div>
                        @empty
                            <p class="px-3 py-4 text-sm text-zinc-500">No products found.</p>
                        @endforelse
                    </div>
                @endif

                <p class="mt-4 text-sm font-medium">Assigned products</p>
                @if ($assignedProducts->isEmpty())
                    <flux:text variant="subtle" class="mt-2 text-sm">No products assigned yet.</flux:text>
                @else
                    <div class="mt-2 flex flex-col gap-2">
                        @foreach ($assignedProducts as $product)
                            <div class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700" wire:key="assigned-{{ $product->getKey() }}">
                                @if ($product->productMedia->first()?->storage_key)
                                    <img src="{{ Storage::disk('public')->url($product->productMedia->first()->storage_key) }}" alt="" class="size-8 rounded object-cover" />
                                @else
                                    <div class="flex size-8 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon.photo class="size-4 text-zinc-400" variant="outline" />
                                    </div>
                                @endif
                                <span class="flex-1 text-sm">{{ $product->title }}</span>
                                <flux:button variant="ghost" size="sm" wire:click="moveProductUp({{ $product->getKey() }})" aria-label="Move up">
                                    <flux:icon.chevron-up class="size-4" variant="outline" />
                                </flux:button>
                                <flux:button variant="ghost" size="sm" wire:click="moveProductDown({{ $product->getKey() }})" aria-label="Move down">
                                    <flux:icon.chevron-down class="size-4" variant="outline" />
                                </flux:button>
                                <flux:button variant="ghost" size="sm" wire:click="removeProduct({{ $product->getKey() }})" aria-label="Remove">
                                    <flux:icon.x-mark class="size-4" variant="outline" />
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="archived">Archived</flux:select.option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>
            </div>
        </div>
    </div>

    <div class="sticky bottom-0 z-10 mt-6 flex justify-end gap-2 border-t border-zinc-200 bg-zinc-100 py-4 dark:border-zinc-700 dark:bg-zinc-950">
        <flux:button variant="ghost" href="{{ route('admin.collections.index') }}" wire:navigate>Discard</flux:button>
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading wire:target="save">Saving...</span>
        </flux:button>
    </div>
</div>
