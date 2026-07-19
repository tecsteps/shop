<div class="pb-20">
    <flux:heading size="xl">{{ $this->isEditing() ? $collection->title : 'Add collection' }}</flux:heading>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{-- Left column (2/3): primary content (spec 03 §5.2) --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label for="title">Title</flux:label>
                    <flux:input id="title" wire:model.blur="title" placeholder="Summer Collection" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field class="mt-4">
                    <flux:label for="handle">Handle</flux:label>
                    <flux:input id="handle" wire:model.blur="handle" placeholder="summer-collection" />
                    <flux:error name="handle" />
                </flux:field>

                <flux:field class="mt-4">
                    <flux:label for="descriptionHtml">Description</flux:label>
                    <flux:textarea id="descriptionHtml" wire:model.blur="descriptionHtml" rows="6" placeholder="Describe this collection..." />
                    <flux:error name="descriptionHtml" />
                </flux:field>

                <flux:field class="mt-4">
                    <flux:label for="type">Type</flux:label>
                    <flux:select id="type" wire:model.live="type">
                        <flux:select.option value="manual">Manual</flux:select.option>
                        <flux:select.option value="automated">Automated</flux:select.option>
                    </flux:select>
                    <flux:error name="type" />
                </flux:field>

                @if ($type === 'automated')
                    <flux:callout class="mt-4" icon="information-circle">
                        Automated collections store the type only for now — rule-based assignment is not implemented yet. Assign products manually below.
                    </flux:callout>
                @endif
            </div>

            {{-- Products assignment (spec 03 §5.2) --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Products</flux:heading>

                <flux:field class="mt-4">
                    <flux:label for="productSearch">Search products</flux:label>
                    <flux:input id="productSearch" icon="magnifying-glass" wire:model.live.debounce.300ms="productSearch" placeholder="Search products..." />
                </flux:field>

                @if ($searchResults->isNotEmpty())
                    <ul class="mt-2 divide-y divide-zinc-100 rounded-lg border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                        @foreach ($searchResults as $result)
                            <li wire:key="result-{{ $result->id }}" class="flex items-center justify-between px-3 py-2">
                                <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $result->title }}</span>
                                <flux:button size="sm" variant="ghost" wire:click="addProduct({{ $result->id }})">Add</flux:button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($assignedProducts->isNotEmpty())
                    <ul class="mt-4 space-y-2">
                        @foreach ($assignedProducts as $index => $product)
                            <li wire:key="assigned-{{ $product->id }}" class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                <span class="flex-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $product->title }}</span>
                                {{-- Reorder via buttons instead of drag-and-drop (spec 03 §5.2 note) --}}
                                <flux:button size="sm" variant="ghost" icon="chevron-up" wire:click="moveProduct({{ $index }}, 'up')" :disabled="$index === 0" aria-label="Move {{ $product->title }} up" />
                                <flux:button size="sm" variant="ghost" icon="chevron-down" wire:click="moveProduct({{ $index }}, 'down')" :disabled="$index === $assignedProducts->count() - 1" aria-label="Move {{ $product->title }} down" />
                                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeProduct({{ $product->id }})" aria-label="Remove {{ $product->title }}" />
                            </li>
                        @endforeach
                    </ul>
                @else
                    <flux:text class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">No products assigned yet. Search above to add products.</flux:text>
                @endif

                <flux:error name="assignedProductIds" />
            </div>
        </div>

        {{-- Right column (1/3): status --}}
        <div>
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label for="status">Status</flux:label>
                    <flux:select id="status" wire:model="status">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="archived">Archived</flux:select.option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>
            </div>
        </div>
    </div>

    {{-- Sticky save bar (spec 03 §19.2) --}}
    <div class="fixed inset-x-0 bottom-0 z-20 border-t border-zinc-200 bg-white/95 backdrop-blur lg:left-64 dark:border-zinc-700 dark:bg-zinc-900/95">
        <div class="mx-auto flex max-w-7xl items-center justify-end gap-3 px-4 py-3 sm:px-6 lg:px-8">
            <flux:button variant="ghost" :href="route('admin.collections.index')" wire:navigate>Discard</flux:button>
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </div>
</div>
