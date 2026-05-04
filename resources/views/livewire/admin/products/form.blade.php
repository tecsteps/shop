<section class="space-y-6 pb-24">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('admin.products.index')" wire:navigate>Products</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $isEditing ? $title : 'Add product' }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <flux:heading size="xl" class="mt-3">{{ $isEditing ? $title : 'Add product' }}</flux:heading>
        </div>

        @if ($isEditing)
            <flux:button wire:click="deleteProduct" variant="danger" icon="archive-box">Archive</flux:button>
        @endif
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="space-y-5">
                    <flux:input wire:model.live.debounce.300ms="title" label="Title" placeholder="Short Sleeve T-Shirt" />
                    <flux:error name="title" />

                    <flux:textarea wire:model="descriptionHtml" label="Description" rows="8" placeholder="Describe your product..." />
                    <flux:error name="descriptionHtml" />
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4">
                    <flux:heading size="lg">Media</flux:heading>
                    <flux:text>{{ count($media) }} {{ Str::plural('item', count($media)) }}</flux:text>
                </div>

                <div class="mt-5 rounded-lg border border-dashed border-zinc-300 p-4 dark:border-zinc-700">
                    <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                        <flux:input
                            wire:model="newMedia"
                            type="file"
                            label="Images"
                            multiple
                            accept="image/*"
                            data-test="product-media-input"
                        />

                        @if ($isEditing)
                            <flux:button type="button" wire:click="uploadMedia" variant="primary" icon="cloud-arrow-up" wire:loading.attr="disabled" wire:target="newMedia,uploadMedia" data-test="product-media-upload-button">
                                Upload
                            </flux:button>
                        @endif
                    </div>

                    <flux:error name="newMedia" />
                    <flux:error name="newMedia.0" />

                    <div wire:loading wire:target="newMedia" class="mt-3 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-full w-1/2 animate-pulse rounded-full bg-zinc-900 dark:bg-zinc-100"></div>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($media as $index => $item)
                        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800" wire:key="product-media-{{ $item['id'] }}">
                            <div class="aspect-square bg-zinc-100 dark:bg-zinc-800">
                                @if ($item['exists'])
                                    <img src="{{ $item['url'] }}" alt="{{ $item['altText'] }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full items-center justify-center text-zinc-400">
                                        <flux:icon.photo class="size-10" />
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-3 p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <flux:badge :color="$item['status'] === 'ready' ? 'green' : ($item['status'] === 'failed' ? 'red' : 'amber')">
                                        {{ Str::headline($item['status']) }}
                                    </flux:badge>

                                    <div class="flex items-center gap-1">
                                        <flux:button type="button" wire:click="moveMedia({{ $item['id'] }}, 'up')" variant="ghost" icon="arrow-up" size="sm" :aria-label="__('Move media up')" />
                                        <flux:button type="button" wire:click="moveMedia({{ $item['id'] }}, 'down')" variant="ghost" icon="arrow-down" size="sm" :aria-label="__('Move media down')" />
                                        <flux:button type="button" wire:click="removeMedia({{ $item['id'] }})" variant="ghost" icon="trash" size="sm" :aria-label="__('Remove media')" />
                                    </div>
                                </div>

                                <div class="grid gap-2">
                                    <flux:input wire:model="media.{{ $index }}.altText" label="Alt text" />
                                    <flux:error name="media.{{ $index }}.altText" />
                                    <flux:button type="button" wire:click="updateMediaAlt({{ $item['id'] }})" variant="ghost" icon="check" size="sm">Save alt text</flux:button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                            No media for this product.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4">
                    <flux:heading size="lg">Variants</flux:heading>
                    <flux:text>{{ count($variants) }} {{ Str::plural('variant', count($variants)) }}</flux:text>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                            <tr>
                                <th class="py-2 pr-3">Variant</th>
                                <th class="px-3 py-2">SKU</th>
                                <th class="px-3 py-2">Price</th>
                                <th class="px-3 py-2">Compare</th>
                                <th class="px-3 py-2">Qty</th>
                                <th class="px-3 py-2">Ship</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach ($variants as $index => $variant)
                                <tr wire:key="product-variant-form-{{ $index }}">
                                    <td class="py-3 pr-3 font-medium">{{ $variant['label'] }}</td>
                                    <td class="px-3 py-3">
                                        <flux:input wire:model="variants.{{ $index }}.sku" aria-label="Variant SKU" />
                                    </td>
                                    <td class="px-3 py-3">
                                        <flux:input wire:model="variants.{{ $index }}.price" type="number" step="0.01" min="0" aria-label="Variant price" />
                                    </td>
                                    <td class="px-3 py-3">
                                        <flux:input wire:model="variants.{{ $index }}.compareAtPrice" type="number" step="0.01" min="0" aria-label="Compare at price" />
                                    </td>
                                    <td class="px-3 py-3">
                                        <flux:input wire:model="variants.{{ $index }}.quantity" type="number" min="0" aria-label="Variant quantity" />
                                    </td>
                                    <td class="px-3 py-3">
                                        <flux:checkbox wire:model="variants.{{ $index }}.requiresShipping" aria-label="Requires shipping" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4">
                    <flux:heading size="lg">Options</flux:heading>
                    <div class="flex flex-wrap justify-end gap-2">
                        <flux:button type="button" wire:click="generateVariants" variant="filled" icon="squares-2x2">Generate variants</flux:button>
                        <flux:button type="button" wire:click="addOption" variant="ghost" icon="plus">Add option</flux:button>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($options as $index => $option)
                        <div class="grid gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700 md:grid-cols-[180px_1fr_auto]" wire:key="product-option-form-{{ $index }}">
                            <flux:input wire:model="options.{{ $index }}.name" label="Option name" placeholder="Size" />
                            <flux:input wire:model="options.{{ $index }}.values" label="Values" placeholder="S, M, L, XL" />
                            <div class="flex items-end">
                                <flux:button type="button" wire:click="removeOption({{ $index }})" variant="ghost" icon="trash">Remove</flux:button>
                            </div>
                        </div>
                    @empty
                        <flux:text>No options for this product.</flux:text>
                    @endforelse
                </div>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="space-y-5">
                    <flux:select wire:model="status" label="Status">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="archived">Archived</flux:select.option>
                    </flux:select>

                    <flux:input wire:model="handle" label="URL handle" placeholder="short-sleeve-t-shirt" />
                    <flux:error name="handle" />
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="space-y-5">
                    <flux:input wire:model="vendor" label="Vendor" placeholder="Acme Basics" />
                    <flux:input wire:model="productType" label="Product type" placeholder="T-Shirts" />
                    <flux:input wire:model="tags" label="Tags" placeholder="new, cotton, sale" />
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">Collections</flux:heading>

                <div class="mt-4 space-y-3">
                    @forelse ($availableCollections as $collection)
                        <flux:checkbox wire:model="collectionIds" value="{{ $collection->getKey() }}" :label="$collection->title" wire:key="product-collection-{{ $collection->getKey() }}" />
                    @empty
                        <flux:text>No collections yet.</flux:text>
                    @endforelse
                </div>
            </div>
        </aside>

        <div class="fixed bottom-0 left-0 right-0 z-40 border-t border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur dark:border-zinc-700 dark:bg-zinc-950/95 lg:left-64">
            <div class="mx-auto flex max-w-7xl justify-end gap-3">
                <flux:button :href="route('admin.products.index')" wire:navigate variant="ghost">Discard</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
            </div>
        </div>
    </form>
</section>
