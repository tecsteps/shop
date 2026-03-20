<div>
    <div class="mb-6">
        <flux:heading size="xl" level="1">{{ $this->isEditing ? $collection->title : 'Add collection' }}</flux:heading>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 space-y-4">
                    <flux:input wire:model="title" label="Title" placeholder="Summer Sale" />
                    <flux:input wire:model="handle" label="Handle" />
                    <flux:textarea wire:model="descriptionHtml" label="Description" rows="4" />
                </div>

                {{-- Products --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="md" class="mb-4">Products</flux:heading>
                    <flux:input wire:model.live.debounce.300ms="productSearch" placeholder="Search products to add..." icon="magnifying-glass" class="mb-3" />

                    @if($this->searchResults->count() > 0)
                        <div class="mb-4 rounded border border-zinc-200 dark:border-zinc-700">
                            @foreach($this->searchResults as $product)
                                <div class="flex items-center justify-between border-b border-zinc-100 px-3 py-2 last:border-0 dark:border-zinc-800">
                                    <span class="text-sm">{{ $product->title }}</span>
                                    <flux:button size="sm" variant="ghost" wire:click="addProduct({{ $product->id }})">Add</flux:button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($this->assignedProducts->count() > 0)
                        <div class="space-y-2">
                            @foreach($this->assignedProducts as $product)
                                <div class="flex items-center justify-between rounded border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                    <span class="text-sm font-medium">{{ $product->title }}</span>
                                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeProduct({{ $product->id }})" />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <flux:text class="text-sm text-zinc-500">No products assigned yet.</flux:text>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:select wire:model="status" label="Status">
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="archived">Archived</flux:select.option>
                    </flux:select>
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 mt-6 flex items-center justify-end gap-2 border-t border-zinc-200 bg-white px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:button variant="ghost" href="{{ route('admin.collections.index') }}" wire:navigate>Discard</flux:button>
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
