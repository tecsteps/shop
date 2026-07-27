<div class="space-y-6 pb-24">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ $this->isEditing() ? $title : 'Add product' }}</flux:heading>

        @if ($this->isEditing())
            @can('archive', $this->product)
                <flux:button variant="danger" icon="trash" wire:click="$set('confirmingDelete', true)">Delete</flux:button>
            @endcan
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left column --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Title & description --}}
            <div class="space-y-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label for="title">Title</flux:label>
                    <flux:input id="title" wire:model.blur="title" placeholder="Short Sleeve T-Shirt" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label for="descriptionHtml">Description</flux:label>
                    <flux:textarea id="descriptionHtml" wire:model.blur="descriptionHtml" rows="8" placeholder="Describe your product..." />
                    <flux:error name="descriptionHtml" />
                </flux:field>
            </div>

            {{-- Media --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Media</flux:heading>

                <label for="newMedia"
                       class="mt-4 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-zinc-300 px-6 py-8 text-center hover:border-zinc-400 dark:border-zinc-600 dark:hover:border-zinc-500">
                    <flux:icon name="arrow-up-tray" class="size-8 text-zinc-400" />
                    <flux:text>Drag and drop images or click to upload</flux:text>
                    <input id="newMedia" type="file" wire:model="newMedia" multiple accept="image/*" class="sr-only" />
                </label>

                <div wire:loading wire:target="newMedia" class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                    <div class="h-full w-1/3 animate-pulse rounded-full bg-blue-500"></div>
                </div>

                <flux:error name="newMedia.*" class="mt-2" />

                @if ($media !== [] || $newMedia !== [])
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($media as $index => $item)
                            <div wire:key="media-{{ $item['id'] }}" class="group relative">
                                <img src="{{ $item['url'] }}" alt="{{ $item['alt_text'] }}" class="aspect-square w-full rounded object-cover">

                                <div class="absolute inset-0 flex flex-col justify-between rounded bg-black/0 p-1.5 opacity-0 transition group-hover:bg-black/40 group-hover:opacity-100">
                                    <div class="flex justify-end gap-1">
                                        <button type="button" wire:click="moveMedia({{ $item['id'] }}, 'up')" class="rounded bg-white/90 p-1 text-zinc-700" aria-label="Move earlier">
                                            <flux:icon name="arrow-left" class="size-3.5" />
                                        </button>
                                        <button type="button" wire:click="moveMedia({{ $item['id'] }}, 'down')" class="rounded bg-white/90 p-1 text-zinc-700" aria-label="Move later">
                                            <flux:icon name="arrow-right" class="size-3.5" />
                                        </button>
                                        <button type="button" wire:click="removeMedia({{ $item['id'] }})" class="rounded bg-white/90 p-1 text-red-600" aria-label="Delete image">
                                            <flux:icon name="x-mark" class="size-3.5" />
                                        </button>
                                    </div>
                                </div>

                                <input type="text"
                                       value="{{ $item['alt_text'] }}"
                                       wire:blur="updateMediaAlt({{ $item['id'] }}, $event.target.value)"
                                       placeholder="Alt text"
                                       aria-label="Alt text for image {{ $index + 1 }}"
                                       class="mt-1 w-full rounded border border-zinc-200 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800" />
                            </div>
                        @endforeach

                        @foreach ($newMedia as $index => $file)
                            <div wire:key="new-media-{{ $index }}" class="group relative">
                                <img src="{{ $file->temporaryUrl() }}" alt="" class="aspect-square w-full rounded object-cover opacity-80">
                                <button type="button" wire:click="removeNewMedia({{ $index }})" class="absolute top-1.5 right-1.5 rounded bg-white/90 p-1 text-red-600" aria-label="Remove upload">
                                    <flux:icon name="x-mark" class="size-3.5" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Variants --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Variants</flux:heading>

                <div class="mt-4 space-y-3">
                    @foreach ($options as $optionIndex => $option)
                        <div wire:key="option-{{ $optionIndex }}" class="flex flex-wrap items-end gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <flux:field class="w-full sm:w-48">
                                <flux:label>Option name</flux:label>
                                <flux:input wire:model.blur="options.{{ $optionIndex }}.name" wire:change="generateVariants" placeholder="Size" />
                            </flux:field>
                            <flux:field class="min-w-48 flex-1">
                                <flux:label>Values (comma-separated)</flux:label>
                                <flux:input wire:model.blur="options.{{ $optionIndex }}.values" wire:change="generateVariants" placeholder="S, M, L, XL" />
                            </flux:field>
                            <flux:button icon="trash" variant="ghost" wire:click="removeOption({{ $optionIndex }})" aria-label="Remove option" />
                        </div>
                    @endforeach
                </div>

                @if (count($options) < 3)
                    <flux:button variant="ghost" icon="plus" wire:click="addOption" class="mt-3">Add another option</flux:button>
                @endif

                @if ($variants !== [])
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[640px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 text-xs tracking-wider text-zinc-500 uppercase dark:border-zinc-700 dark:text-zinc-400">
                                    <th class="py-2 pr-3 font-medium">Variant</th>
                                    <th class="py-2 pr-3 font-medium">SKU</th>
                                    <th class="py-2 pr-3 font-medium">Barcode</th>
                                    <th class="py-2 pr-3 font-medium">Price (cents)</th>
                                    <th class="py-2 pr-3 font-medium">Compare at</th>
                                    <th class="py-2 pr-3 font-medium">Weight (g)</th>
                                    <th class="py-2 pr-3 font-medium">Qty</th>
                                    <th class="py-2 pr-3 font-medium">Policy</th>
                                    <th class="py-2 font-medium">Ship</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($variants as $variantIndex => $variant)
                                    <tr wire:key="variant-{{ $variant['key'] }}-{{ $variantIndex }}">
                                        <td class="py-2 pr-3 whitespace-nowrap text-zinc-900 dark:text-zinc-100">
                                            {{ $variant['label'] }}
                                            @if ($loop->first)
                                                <flux:badge size="sm" color="zinc" class="ml-1">Default</flux:badge>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-3">
                                            <input type="text" wire:model.blur="variants.{{ $variantIndex }}.sku" aria-label="SKU" class="w-28 rounded border border-zinc-200 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800" />
                                            <flux:error name="variants.{{ $variantIndex }}.sku" />
                                        </td>
                                        <td class="py-2 pr-3">
                                            <input type="text" wire:model.blur="variants.{{ $variantIndex }}.barcode" aria-label="Barcode" class="w-24 rounded border border-zinc-200 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800" />
                                        </td>
                                        <td class="py-2 pr-3">
                                            <input type="number" min="0" wire:model.blur="variants.{{ $variantIndex }}.price" aria-label="Price in cents" class="w-24 rounded border border-zinc-200 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800" />
                                            <flux:error name="variants.{{ $variantIndex }}.price" />
                                        </td>
                                        <td class="py-2 pr-3">
                                            <input type="number" min="0" wire:model.blur="variants.{{ $variantIndex }}.compareAtPrice" aria-label="Compare at price in cents" class="w-24 rounded border border-zinc-200 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800" />
                                        </td>
                                        <td class="py-2 pr-3">
                                            <input type="number" min="0" wire:model.blur="variants.{{ $variantIndex }}.weight_g" aria-label="Weight in grams" class="w-20 rounded border border-zinc-200 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800" />
                                        </td>
                                        <td class="py-2 pr-3">
                                            <input type="number" min="0" wire:model.blur="variants.{{ $variantIndex }}.quantity" aria-label="Quantity on hand" class="w-20 rounded border border-zinc-200 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800" />
                                            <flux:error name="variants.{{ $variantIndex }}.quantity" />
                                        </td>
                                        <td class="py-2 pr-3">
                                            <select wire:model.blur="variants.{{ $variantIndex }}.policy" aria-label="Inventory policy" class="rounded border border-zinc-200 bg-white px-2 py-1 text-xs dark:border-zinc-700 dark:bg-zinc-800">
                                                <option value="deny">Deny</option>
                                                <option value="continue">Continue</option>
                                            </select>
                                        </td>
                                        <td class="py-2">
                                            <flux:checkbox wire:model.blur="variants.{{ $variantIndex }}.requiresShipping" aria-label="Requires shipping" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- SEO --}}
            <div x-data="{ open: false }" class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <button type="button" @click="open = !open" :aria-expanded="open.toString()" class="flex items-center gap-2 font-medium text-zinc-900 dark:text-zinc-100">
                    <flux:icon name="chevron-right" class="size-4 transition-transform" x-bind:class="{ 'rotate-90': open }" />
                    Search engine listing
                </button>

                <div x-show="open" x-collapse class="mt-4">
                    <flux:field>
                        <flux:label for="handle">URL handle</flux:label>
                        <flux:input id="handle" wire:model.blur="handle" placeholder="short-sleeve-t-shirt" />
                        <flux:error name="handle" />
                    </flux:field>
                </div>
            </div>
        </div>

        {{-- Right column --}}
        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label for="status">Status</flux:label>
                    <flux:select id="status" wire:model.blur="status">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="archived">Archived</flux:select.option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label for="publishedAt">Published at</flux:label>
                    <flux:input id="publishedAt" type="datetime-local" wire:model.blur="publishedAt" />
                    <flux:error name="publishedAt" />
                </flux:field>
            </div>

            <div class="space-y-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Organization</flux:heading>

                <flux:field>
                    <flux:label for="vendor">Vendor</flux:label>
                    <flux:input id="vendor" wire:model.blur="vendor" placeholder="Nike" />
                    <flux:error name="vendor" />
                </flux:field>

                <flux:field>
                    <flux:label for="productType">Product type</flux:label>
                    <flux:input id="productType" wire:model.blur="productType" placeholder="T-Shirts" />
                    <flux:error name="productType" />
                </flux:field>

                <flux:field>
                    <flux:label for="tags">Tags</flux:label>
                    <flux:input id="tags" wire:model.blur="tags" placeholder="summer, cotton, sale" />
                    <flux:description>Separate tags with commas</flux:description>
                    <flux:error name="tags" />
                </flux:field>
            </div>

            @if ($availableCollections->isNotEmpty())
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="md">Collections</flux:heading>

                    <div class="mt-3 space-y-2">
                        @foreach ($availableCollections as $collection)
                            <flux:checkbox wire:model.blur="collectionIds" value="{{ $collection->id }}" :label="$collection->title" wire:key="collection-{{ $collection->id }}" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Sticky save bar --}}
    <div class="fixed inset-x-0 bottom-0 z-20 border-t border-zinc-200 bg-white/95 backdrop-blur lg:left-64 dark:border-zinc-700 dark:bg-zinc-900/95">
        <div class="mx-auto flex max-w-7xl items-center justify-end gap-3 px-4 py-3 sm:px-6 lg:px-8">
            <flux:button variant="ghost" :href="route('admin.products.index')" wire:navigate>Discard</flux:button>
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </div>

    {{-- Delete confirmation modal (edit only, spec 03 §4) --}}
    @if ($this->isEditing())
        <flux:modal wire:model="confirmingDelete" name="confirm-delete-product" class="max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">Delete this product?</flux:heading>
                <flux:text>This product will be archived. Products with existing orders cannot be permanently removed.</flux:text>
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('confirmingDelete', false)">Cancel</flux:button>
                    <flux:button variant="danger" wire:click="deleteProduct">Delete</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
