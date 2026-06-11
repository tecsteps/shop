<div class="space-y-6 pb-24">
    <x-admin.breadcrumbs :items="[
        ['label' => __('Products'), 'href' => route('admin.products.index')],
        ['label' => $this->isEditing ? $product->title : __('Add product')],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="1">
            {{ $this->isEditing ? $product->title : __('Add product') }}
        </flux:heading>

        @if ($this->isEditing)
            @can('delete', $product)
                <flux:modal.trigger name="confirm-delete-product">
                    <flux:button variant="danger" data-test="delete-product-button">{{ __('Delete') }}</flux:button>
                </flux:modal.trigger>
            @endcan
        @endif
    </div>

    @error('variants')
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.text>{{ $message }}</flux:callout.text>
        </flux:callout>
    @enderror

    <form wire:submit="save" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- LEFT COLUMN (2/3) --}}
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Title') }}</flux:label>
                    <flux:input wire:model="title" :placeholder="__('Short Sleeve T-Shirt')" data-test="product-title-input" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Description') }}</flux:label>
                    <flux:textarea wire:model="descriptionHtml" rows="8" :placeholder="__('Describe your product...')" data-test="product-description-input" />
                    <flux:error name="descriptionHtml" />
                </flux:field>
            </x-admin.card>

            {{-- Media --}}
            <x-admin.card :heading="__('Media')">
                <label
                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-zinc-300 px-6 py-8 text-center transition hover:border-zinc-400 dark:border-zinc-600 dark:hover:border-zinc-500"
                >
                    <flux:icon name="arrow-up-tray" class="size-6 text-zinc-400" />
                    <flux:text>{{ __('Drag and drop images or click to upload') }}</flux:text>
                    <input type="file" wire:model="newMedia" multiple accept="image/*" class="sr-only" data-test="media-upload-input" />
                </label>

                <div wire:loading wire:target="newMedia" class="mt-3">
                    <flux:text>{{ __('Uploading...') }}</flux:text>
                </div>

                <flux:error name="newMedia.*" />

                @if ($this->isEditing && $this->mediaItems->isNotEmpty())
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" wire:sort="reorderMedia">
                        @foreach ($this->mediaItems as $media)
                            <div
                                wire:key="media-{{ $media->id }}"
                                wire:sort.item="{{ $media->id }}"
                                x-data="{ editingAlt: false, alt: @js($media->alt_text ?? '') }"
                                class="group relative aspect-square cursor-grab overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700"
                            >
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($media->storage_key) }}"
                                    alt="{{ $media->alt_text ?? '' }}"
                                    class="size-full object-cover"
                                />

                                <button
                                    type="button"
                                    wire:click="removeMedia({{ $media->id }})"
                                    class="absolute top-1.5 right-1.5 hidden cursor-pointer rounded-full bg-zinc-900/70 p-1 text-white group-hover:block"
                                    aria-label="{{ __('Remove image') }}"
                                >
                                    <flux:icon name="x-mark" variant="micro" />
                                </button>

                                <button
                                    type="button"
                                    x-on:click="editingAlt = true"
                                    class="absolute bottom-1.5 left-1.5 hidden cursor-pointer rounded-full bg-zinc-900/70 p-1 text-white group-hover:block"
                                    aria-label="{{ __('Edit alt text') }}"
                                >
                                    <flux:icon name="pencil" variant="micro" />
                                </button>

                                <div x-show="editingAlt" x-cloak class="absolute inset-x-0 bottom-0 flex gap-1 bg-zinc-900/80 p-1.5">
                                    <input
                                        type="text"
                                        x-model="alt"
                                        x-on:keydown.enter.prevent="$wire.updateMediaAlt({{ $media->id }}, alt); editingAlt = false"
                                        placeholder="{{ __('Alt text') }}"
                                        aria-label="{{ __('Alt text') }}"
                                        class="w-full rounded border-0 bg-white/90 px-1.5 py-0.5 text-xs text-zinc-900"
                                    />
                                    <button
                                        type="button"
                                        x-on:click="$wire.updateMediaAlt({{ $media->id }}, alt); editingAlt = false"
                                        class="cursor-pointer text-xs font-semibold text-white"
                                    >
                                        {{ __('Save') }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (! $this->isEditing && count($pendingMedia) > 0)
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($pendingMedia as $index => $file)
                            <div wire:key="pending-media-{{ $index }}" class="group relative aspect-square overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                                <img src="{{ $file->temporaryUrl() }}" alt="" class="size-full object-cover" />
                                <button
                                    type="button"
                                    wire:click="removePendingMedia({{ $index }})"
                                    class="absolute top-1.5 right-1.5 hidden cursor-pointer rounded-full bg-zinc-900/70 p-1 text-white group-hover:block"
                                    aria-label="{{ __('Remove image') }}"
                                >
                                    <flux:icon name="x-mark" variant="micro" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>

            {{-- Variants --}}
            <x-admin.card :heading="__('Variants')">
                <div class="space-y-3">
                    @foreach ($options as $index => $option)
                        <div wire:key="option-{{ $index }}" class="flex flex-wrap items-end gap-3">
                            <flux:field class="w-40">
                                <flux:label>{{ __('Option name') }}</flux:label>
                                <flux:input wire:model.live.debounce.500ms="options.{{ $index }}.name" :placeholder="__('Size')" data-test="option-name-{{ $index }}" />
                            </flux:field>

                            <flux:field class="min-w-52 flex-1">
                                <flux:label>{{ __('Values') }}</flux:label>
                                <flux:input wire:model.live.debounce.500ms="options.{{ $index }}.values" placeholder="S, M, L, XL" data-test="option-values-{{ $index }}" />
                                <flux:description>{{ __('Separate values with commas') }}</flux:description>
                            </flux:field>

                            <flux:button
                                variant="ghost"
                                icon="trash"
                                wire:click="removeOption({{ $index }})"
                                aria-label="{{ __('Remove option') }}"
                            />
                        </div>
                    @endforeach

                    <flux:button variant="ghost" size="sm" icon="plus" wire:click="addOption" data-test="add-option-button">
                        {{ __('Add another option') }}
                    </flux:button>
                </div>

                @if (count($variants) > 0)
                    <div class="mt-5 overflow-x-auto">
                        <table class="w-full min-w-[640px] text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 text-left text-xs font-semibold text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                                    <th class="py-2 pr-3">{{ __('Variant') }}</th>
                                    <th class="py-2 pr-3">{{ __('SKU') }}</th>
                                    <th class="py-2 pr-3">{{ __('Barcode') }}</th>
                                    <th class="py-2 pr-3">{{ __('Price') }}</th>
                                    <th class="py-2 pr-3">{{ __('Compare at') }}</th>
                                    <th class="py-2 pr-3">{{ __('Weight (g)') }}</th>
                                    <th class="py-2 pr-3">{{ __('Qty') }}</th>
                                    <th class="py-2">{{ __('Ship') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($variants as $index => $variant)
                                    <tr wire:key="variant-{{ $variant['key'] }}">
                                        <td class="py-2 pr-3 font-medium whitespace-nowrap text-zinc-800 dark:text-zinc-200">
                                            {{ $variant['label'] }}
                                        </td>
                                        <td class="py-2 pr-3"><flux:input wire:model="variants.{{ $index }}.sku" size="sm" class="min-w-24" /></td>
                                        <td class="py-2 pr-3"><flux:input wire:model="variants.{{ $index }}.barcode" size="sm" class="min-w-24" /></td>
                                        <td class="py-2 pr-3">
                                            <flux:input wire:model="variants.{{ $index }}.price" type="number" step="0.01" min="0" size="sm" class="min-w-24" data-test="variant-price-{{ $index }}" />
                                            <flux:error name="variants.{{ $index }}.price" />
                                        </td>
                                        <td class="py-2 pr-3"><flux:input wire:model="variants.{{ $index }}.compareAtPrice" type="number" step="0.01" min="0" size="sm" class="min-w-24" /></td>
                                        <td class="py-2 pr-3"><flux:input wire:model="variants.{{ $index }}.weight" type="number" min="0" size="sm" class="min-w-20" /></td>
                                        <td class="py-2 pr-3">
                                            <flux:input wire:model="variants.{{ $index }}.quantity" type="number" min="0" size="sm" class="min-w-20" data-test="variant-quantity-{{ $index }}" />
                                            <flux:error name="variants.{{ $index }}.quantity" />
                                        </td>
                                        <td class="py-2"><flux:checkbox wire:model="variants.{{ $index }}.requiresShipping" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-admin.card>

            {{-- SEO (collapsible) --}}
            <x-admin.card x-data="{ expanded: false }">
                <button
                    type="button"
                    x-on:click="expanded = ! expanded"
                    :aria-expanded="expanded"
                    class="flex w-full cursor-pointer items-center gap-2 text-left"
                >
                    <flux:icon name="chevron-right" variant="micro" x-bind:class="expanded ? 'rotate-90' : ''" class="transition-transform" />
                    <flux:heading>{{ __('Search engine listing') }}</flux:heading>
                </button>

                <div x-show="expanded" x-transition.opacity x-cloak class="mt-4">
                    <flux:field>
                        <flux:label>{{ __('URL handle') }}</flux:label>
                        <flux:input wire:model="handle" placeholder="short-sleeve-t-shirt" data-test="product-handle-input" />
                        <flux:description>{{ __('Leave empty to generate from the title.') }}</flux:description>
                        <flux:error name="handle" />
                    </flux:field>
                </div>
            </x-admin.card>
        </div>

        {{-- RIGHT COLUMN (1/3) --}}
        <div class="space-y-6">
            <x-admin.card :heading="__('Status')">
                <flux:field>
                    <flux:select wire:model="status" data-test="product-status-select">
                        <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                        <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                        <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>
            </x-admin.card>

            <x-admin.card :heading="__('Publishing')">
                <flux:field>
                    <flux:label>{{ __('Published at') }}</flux:label>
                    <flux:input wire:model="publishedAt" type="datetime-local" />
                    <flux:error name="publishedAt" />
                </flux:field>
            </x-admin.card>

            <x-admin.card :heading="__('Product organization')" class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Vendor') }}</flux:label>
                    <flux:input wire:model="vendor" placeholder="Nike" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Product type') }}</flux:label>
                    <flux:input wire:model="productType" :placeholder="__('T-Shirts')" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Tags') }}</flux:label>
                    <flux:input wire:model="tags" placeholder="summer, cotton, sale" />
                    <flux:description>{{ __('Separate tags with commas') }}</flux:description>
                </flux:field>
            </x-admin.card>

            <x-admin.card :heading="__('Collections')">
                @if ($this->availableCollections->isEmpty())
                    <flux:text>{{ __('No collections yet.') }}</flux:text>
                @else
                    <div class="max-h-56 space-y-2 overflow-y-auto">
                        @foreach ($this->availableCollections as $collection)
                            <flux:checkbox
                                wire:key="collection-{{ $collection->id }}"
                                wire:model="collectionIds"
                                value="{{ $collection->id }}"
                                :label="$collection->title"
                            />
                        @endforeach
                    </div>
                @endif
            </x-admin.card>
        </div>

        {{-- Sticky save bar --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 backdrop-blur lg:pl-64 dark:border-zinc-700 dark:bg-zinc-900/95">
            <div class="mx-auto flex max-w-7xl items-center justify-end gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <flux:button variant="ghost" :href="route('admin.products.index')" wire:navigate>
                    {{ __('Discard') }}
                </flux:button>
                <flux:button type="submit" variant="primary" data-test="save-product-button">
                    <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </flux:button>
            </div>
        </div>
    </form>

    @if ($this->isEditing)
        <flux:modal name="confirm-delete-product" class="md:max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Delete this product?') }}</flux:heading>
                <flux:text>
                    {{ __('This product will be archived. Products with existing orders cannot be permanently removed.') }}
                </flux:text>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="deleteProduct" data-test="confirm-delete-product-button">
                        {{ __('Delete product') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
