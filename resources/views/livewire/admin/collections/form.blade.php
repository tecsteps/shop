<div class="space-y-6 pb-24">
    <x-admin.breadcrumbs :items="[
        ['label' => __('Collections'), 'href' => route('admin.collections.index')],
        ['label' => $this->isEditing ? $collection->title : __('Add collection')],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="1">
            {{ $this->isEditing ? $collection->title : __('Add collection') }}
        </flux:heading>

        @if ($this->isEditing)
            @can('delete', $collection)
                <flux:modal.trigger name="confirm-delete-collection">
                    <flux:button variant="danger" data-test="delete-collection-button">{{ __('Delete') }}</flux:button>
                </flux:modal.trigger>
            @endcan
        @endif
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- LEFT COLUMN (2/3) --}}
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Title') }}</flux:label>
                    <flux:input wire:model="title" :placeholder="__('Summer Collection')" data-test="collection-title-input" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Handle') }}</flux:label>
                    <flux:input wire:model="handle" placeholder="summer-collection" data-test="collection-handle-input" />
                    <flux:description>{{ __('Leave empty to generate from the title.') }}</flux:description>
                    <flux:error name="handle" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Description') }}</flux:label>
                    <flux:textarea wire:model="descriptionHtml" rows="6" :placeholder="__('Describe this collection...')" data-test="collection-description-input" />
                    <flux:error name="descriptionHtml" />
                </flux:field>
            </x-admin.card>

            {{-- Product assignment --}}
            <x-admin.card :heading="__('Products')">
                <div class="relative">
                    <flux:input
                        wire:model.live.debounce.300ms="productSearch"
                        icon="magnifying-glass"
                        :placeholder="__('Search products...')"
                        data-test="collection-product-search"
                    />

                    @if ($this->searchResults->isNotEmpty())
                        <div class="absolute inset-x-0 top-full z-20 mt-1 max-h-64 overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                            @foreach ($this->searchResults as $product)
                                <div wire:key="search-result-{{ $product->id }}" class="flex items-center justify-between gap-3 px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <flux:text class="truncate">{{ $product->title }}</flux:text>
                                    <flux:button variant="ghost" size="sm" wire:click="addProduct({{ $product->id }})" data-test="add-product-{{ $product->id }}">
                                        {{ __('Add') }}
                                    </flux:button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($this->assignedProducts->isEmpty())
                    <flux:text class="mt-4">{{ __('No products assigned yet. Search above to add products.') }}</flux:text>
                @else
                    <div class="mt-4 space-y-2" wire:sort="reorderProducts">
                        @foreach ($this->assignedProducts as $product)
                            <div
                                wire:key="assigned-{{ $product->id }}"
                                wire:sort.item="{{ $product->id }}"
                                class="flex cursor-grab items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
                            >
                                <flux:icon name="bars-3" variant="micro" class="shrink-0 text-zinc-400" />

                                @if ($product->media->isNotEmpty())
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->media->first()->storage_key) }}"
                                        alt="{{ $product->media->first()->alt_text ?? $product->title }}"
                                        class="size-9 rounded-md object-cover"
                                    />
                                @else
                                    <div class="flex size-9 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-700">
                                        <flux:icon name="photo" variant="micro" class="text-zinc-400" />
                                    </div>
                                @endif

                                <span class="flex-1 truncate text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $product->title }}</span>

                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="x-mark"
                                    wire:click="removeProduct({{ $product->id }})"
                                    aria-label="{{ __('Remove :title', ['title' => $product->title]) }}"
                                    data-test="remove-product-{{ $product->id }}"
                                />
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>
        </div>

        {{-- RIGHT COLUMN (1/3) --}}
        <div class="space-y-6">
            <x-admin.card :heading="__('Status')">
                <flux:field>
                    <flux:select wire:model="status" data-test="collection-status-select">
                        <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                        <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                        <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>
            </x-admin.card>
        </div>

        {{-- Sticky save bar --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 backdrop-blur lg:pl-64 dark:border-zinc-700 dark:bg-zinc-900/95">
            <div class="mx-auto flex max-w-7xl items-center justify-end gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <flux:button variant="ghost" :href="route('admin.collections.index')" wire:navigate>
                    {{ __('Discard') }}
                </flux:button>
                <flux:button type="submit" variant="primary" data-test="save-collection-button">
                    <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </flux:button>
            </div>
        </div>
    </form>

    @if ($this->isEditing)
        <flux:modal name="confirm-delete-collection" class="md:max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Delete this collection?') }}</flux:heading>
                <flux:text>
                    {{ __('The collection will be removed. Products in the collection are not deleted.') }}
                </flux:text>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="deleteCollection" data-test="confirm-delete-collection-button">
                        {{ __('Delete collection') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
