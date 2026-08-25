<div class="pb-24">
    <flux:heading size="xl">
        {{ $this->isEditing ? $collection->title : 'Add collection' }}
    </flux:heading>

    <form wire:submit="save">
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left column --}}
            <div class="space-y-6 lg:col-span-2">
                <flux:card class="p-6">
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Title</flux:label>
                            <flux:input wire:model="title" placeholder="Summer Collection" />
                            <flux:error name="title" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Handle</flux:label>
                            <flux:input wire:model="handle" placeholder="summer-collection" />
                            <flux:error name="handle" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Description</flux:label>
                            <flux:textarea wire:model="descriptionHtml" rows="6" placeholder="Describe this collection..." />
                            <flux:error name="descriptionHtml" />
                        </flux:field>
                    </div>
                </flux:card>

                {{-- Products assignment --}}
                <flux:card class="p-6">
                    <flux:heading size="md">Products</flux:heading>

                    <div class="mt-4">
                        <flux:input
                            wire:model.live.debounce.300ms="productSearch"
                            icon="magnifying-glass"
                            placeholder="Search products..."
                        />

                        @if ($this->productSearch !== '' && $this->searchResults->isNotEmpty())
                            <div class="mt-2 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                                @foreach ($this->searchResults as $product)
                                    <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-3 py-2 last:border-0 dark:border-zinc-800">
                                        <span class="truncate text-sm">{{ $product->title }}</span>
                                        <flux:button variant="ghost" size="sm" wire:click="addProduct({{ $product->id }})">
                                            Add
                                        </flux:button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 space-y-2">
                        @forelse ($this->assignedProducts as $product)
                            <div class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                <div class="flex min-w-0 items-center gap-3">
                                    <flux:icon.bars-3 class="size-4 shrink-0 text-zinc-400" />
                                    <span class="truncate text-sm font-medium">{{ $product->title }}</span>
                                </div>
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="x-mark"
                                    wire:click="removeProduct({{ $product->id }})"
                                    aria-label="Remove {{ $product->title }}"
                                />
                            </div>
                        @empty
                            <flux:text>No products assigned yet.</flux:text>
                        @endforelse
                    </div>
                </flux:card>
            </div>

            {{-- Right column --}}
            <div class="space-y-6">
                <flux:card class="p-6">
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <option value="active">Active</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>
                </flux:card>
            </div>
        </div>

        {{-- Sticky save bar --}}
        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95 lg:start-64">
            <div class="mx-auto flex max-w-7xl items-center justify-end gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <flux:button variant="ghost" :href="route('admin.collections.index')" wire:navigate>Discard</flux:button>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Save</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>
        </div>
    </form>
</div>
