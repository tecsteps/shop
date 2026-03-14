<div>
    {{-- Breadcrumbs --}}
    <div class="mb-4">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.collections.index')" wire:navigate>Collections</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $this->isEditing ? $title : 'Add collection' }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    {{-- Header --}}
    <div class="mb-6">
        <flux:heading size="xl">{{ $this->isEditing ? $title : 'Add collection' }}</flux:heading>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left column --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Title, Handle, Description --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 space-y-4">
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

                {{-- Products --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <flux:heading size="md" class="mb-4">Products</flux:heading>

                    {{-- Product search --}}
                    <div class="relative mb-4">
                        <flux:input
                            wire:model.live.debounce.300ms="productSearch"
                            icon="magnifying-glass"
                            placeholder="Search products to add..."
                        />

                        @if ($this->searchResults->count() > 0)
                            <div class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg z-10 max-h-48 overflow-y-auto">
                                @foreach ($this->searchResults as $result)
                                    <button
                                        type="button"
                                        wire:key="search-{{ $result->id }}"
                                        wire:click="addProduct({{ $result->id }})"
                                        class="flex items-center justify-between w-full px-4 py-2 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700/50 text-left"
                                    >
                                        <span class="text-zinc-900 dark:text-white">{{ $result->title }}</span>
                                        <flux:badge size="sm">Add</flux:badge>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Assigned products --}}
                    @if ($this->assignedProducts->count() > 0)
                        <div class="space-y-2">
                            @foreach ($this->assignedProducts as $product)
                                <div
                                    wire:key="assigned-{{ $product->id }}"
                                    class="flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg"
                                >
                                    @if ($product->media->first())
                                        <img
                                            src="{{ $product->media->first()->url }}"
                                            alt="{{ $product->title }}"
                                            class="size-10 rounded object-cover shrink-0"
                                        >
                                    @else
                                        <div class="size-10 rounded bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center shrink-0">
                                            <flux:icon name="photo" class="size-5 text-zinc-400" />
                                        </div>
                                    @endif

                                    <span class="flex-1 text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ $product->title }}
                                    </span>

                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="x-mark"
                                        wire:click="removeProduct({{ $product->id }})"
                                    />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm">No products assigned yet. Search above to add products.</flux:text>
                    @endif
                </div>
            </div>

            {{-- Right column --}}
            <div class="space-y-6">
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
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

        {{-- Sticky save bar --}}
        <div class="fixed bottom-0 left-0 lg:left-64 right-0 bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 px-6 py-3 flex justify-end gap-3 z-10">
            <flux:button variant="ghost" :href="route('admin.collections.index')" wire:navigate>
                Discard
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>

        <div class="h-20"></div>
    </form>
</div>
