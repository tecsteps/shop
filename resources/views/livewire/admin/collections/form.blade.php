<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ $this->isEditing ? $title : 'Add collection' }}</flux:heading>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
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
                            <flux:textarea wire:model="descriptionHtml" rows="4" placeholder="Describe this collection..." />
                        </flux:field>
                    </div>
                </div>

                {{-- Products --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="md" class="mb-4">Products</flux:heading>
                    <flux:input wire:model.live.debounce.300ms="productSearch" placeholder="Search products..." icon="magnifying-glass" class="mb-4" />

                    @if ($this->searchResults->isNotEmpty())
                        <div class="mb-4 rounded border border-zinc-200 dark:border-zinc-700">
                            @foreach ($this->searchResults as $product)
                                <div class="flex items-center justify-between border-b border-zinc-100 px-3 py-2 last:border-b-0 dark:border-zinc-700/50">
                                    <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $product->title }}</span>
                                    <flux:button size="sm" variant="ghost" wire:click="addProduct({{ $product->id }})">Add</flux:button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (count($assignedProductIds) > 0)
                        <div class="space-y-2">
                            @foreach ($this->assignedProducts as $product)
                                <div class="flex items-center justify-between rounded border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                    <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $product->title }}</span>
                                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeProduct({{ $product->id }})" />
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="active">Active</flux:select.option>
                            <flux:select.option value="archived">Archived</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white px-6 py-3 dark:border-zinc-700 dark:bg-zinc-800 lg:left-64">
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" href="{{ route('admin.collections.index') }}" wire:navigate>Discard</flux:button>
                <flux:button variant="primary" type="submit">Save</flux:button>
            </div>
        </div>
    </form>
</div>
