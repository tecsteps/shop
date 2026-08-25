<div class="pb-24">
    <flux:heading size="xl">
        {{ $this->isEditing ? $product->title : 'Add product' }}
    </flux:heading>

    <form wire:submit="save">
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left column --}}
            <div class="space-y-6 lg:col-span-2">
                {{-- Title --}}
                <flux:card class="p-6">
                    <flux:field>
                        <flux:label>Title</flux:label>
                        <flux:input wire:model="title" placeholder="Short Sleeve T-Shirt" />
                        <flux:error name="title" />
                    </flux:field>
                </flux:card>

                {{-- Description --}}
                <flux:card class="p-6">
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="descriptionHtml" rows="8" placeholder="Describe your product..." />
                        <flux:error name="descriptionHtml" />
                    </flux:field>
                </flux:card>

                {{-- Media --}}
                <flux:card class="p-6">
                    <flux:heading size="md">Media</flux:heading>

                    <div wire:loading wire:target="newMedia" class="mt-4">
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full w-full animate-pulse rounded-full bg-zinc-400 dark:bg-zinc-600"></div>
                        </div>
                    </div>

                    @if ($existingMedia !== [] || $newMedia !== [])
                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($existingMedia as $media)
                                <div class="group relative aspect-square overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    <img
                                        src="{{ $media['url'] }}"
                                        alt="{{ $media['alt_text'] }}"
                                        class="size-full object-cover"
                                    />
                                    <div class="absolute inset-0 bg-zinc-900/0 transition group-hover:bg-zinc-900/30"></div>
                                    <button
                                        type="button"
                                        wire:click="removeMedia({{ $media['id'] }})"
                                        class="absolute end-1 top-1 hidden rounded-md bg-white/90 p-1 text-zinc-700 hover:bg-white group-hover:block dark:bg-zinc-900/90 dark:text-zinc-200"
                                        aria-label="Remove media"
                                    >
                                        <flux:icon.x-mark class="size-4" />
                                    </button>
                                    <input
                                        type="text"
                                        value="{{ $media['alt_text'] }}"
                                        wire:change="updateMediaAlt({{ $media['id'] }}, $event.target.value)"
                                        placeholder="Alt text"
                                        class="absolute bottom-1 start-1 hidden w-[calc(100%-0.5rem)] rounded-md bg-white/90 px-1.5 py-0.5 text-xs text-zinc-700 group-hover:block dark:bg-zinc-900/90 dark:text-zinc-200"
                                    />
                                </div>
                            @endforeach

                            @foreach ($newMedia as $file)
                                <div class="relative aspect-square overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    <img src="{{ $file->temporaryUrl() }}" alt="" class="size-full object-cover" />
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <label
                        wire:loading.class="opacity-50"
                        class="mt-4 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-zinc-300 px-4 py-8 text-center text-sm text-zinc-500 transition hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800"
                    >
                        <input type="file" wire:model="newMedia" multiple accept="image/*" class="hidden" />
                        <flux:icon.arrow-up-tray class="size-6" />
                        Drag and drop images or click to upload
                    </label>
                    <flux:error name="newMedia" />
                </flux:card>

                {{-- Variants --}}
                <flux:card class="p-6">
                    <flux:heading size="md">Variants</flux:heading>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 text-start text-xs uppercase tracking-wider text-zinc-400 dark:border-zinc-700">
                                    <th class="py-2 pe-3 text-start font-medium">SKU</th>
                                    <th class="py-2 pe-3 text-start font-medium">Price</th>
                                    <th class="py-2 pe-3 text-start font-medium">Compare at</th>
                                    <th class="py-2 pe-3 text-start font-medium">Quantity</th>
                                    <th class="py-2 pe-3 text-start font-medium">Ship</th>
                                    <th class="py-2 text-start font-medium"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($variants as $index => $variant)
                                    <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                                        <td class="py-2 pe-3">
                                            <flux:input wire:model="variants.{{ $index }}.sku" placeholder="SKU" class="w-28" />
                                        </td>
                                        <td class="py-2 pe-3">
                                            <flux:input wire:model="variants.{{ $index }}.price" type="number" step="0.01" min="0" class="w-28" />
                                        </td>
                                        <td class="py-2 pe-3">
                                            <flux:input wire:model="variants.{{ $index }}.compareAtPrice" type="number" step="0.01" min="0" placeholder="—" class="w-28" />
                                        </td>
                                        <td class="py-2 pe-3">
                                            <flux:input wire:model="variants.{{ $index }}.quantity" type="number" min="0" class="w-24" />
                                        </td>
                                        <td class="py-2 pe-3">
                                            <flux:checkbox wire:model="variants.{{ $index }}.requiresShipping" aria-label="Requires shipping" />
                                        </td>
                                        <td class="py-2 text-end">
                                            <flux:button
                                                variant="ghost"
                                                icon="trash"
                                                size="sm"
                                                wire:click="removeVariant({{ $index }})"
                                                aria-label="Remove variant"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <flux:error name="variants" class="mt-2" />

                    <flux:button variant="ghost" icon="plus" wire:click="addVariant" class="mt-3">
                        Add variant
                    </flux:button>
                </flux:card>

                {{-- SEO --}}
                <flux:card class="p-6">
                    <button type="button" wire:click="$toggle('showSeo')" class="flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        <flux:icon :name="$showSeo ? 'chevron-down' : 'chevron-right'" class="size-4" />
                        Search engine listing
                    </button>

                    @if ($showSeo)
                        <div class="mt-4">
                            <flux:field>
                                <flux:label>URL handle</flux:label>
                                <flux:input wire:model="handle" placeholder="short-sleeve-t-shirt" />
                                <flux:error name="handle" />
                            </flux:field>
                        </div>
                    @endif
                </flux:card>
            </div>

            {{-- Right column --}}
            <div class="space-y-6">
                <flux:card class="p-6">
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>
                </flux:card>

                <flux:card class="p-6">
                    <flux:field>
                        <flux:label>Published at</flux:label>
                        <flux:input type="datetime-local" wire:model="publishedAt" />
                    </flux:field>
                </flux:card>

                <flux:card class="p-6">
                    <flux:heading size="md">Organization</flux:heading>

                    <div class="mt-4 space-y-4">
                        <flux:field>
                            <flux:label>Vendor</flux:label>
                            <flux:input wire:model="vendor" placeholder="Nike" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Product type</flux:label>
                            <flux:input wire:model="productType" placeholder="T-Shirts" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Tags</flux:label>
                            <flux:input wire:model="tags" placeholder="summer, cotton, sale" />
                            <flux:description>Separate tags with commas</flux:description>
                        </flux:field>
                    </div>
                </flux:card>

                <flux:card class="p-6">
                    <flux:heading size="md">Collections</flux:heading>

                    <div class="mt-4 space-y-2">
                        @forelse ($this->availableCollections as $collection)
                            <label class="flex items-center gap-2 text-sm">
                                <flux:checkbox wire:model="collectionIds" value="{{ $collection->id }}" />
                                {{ $collection->title }}
                            </label>
                        @empty
                            <flux:text>No collections yet.</flux:text>
                        @endforelse
                    </div>
                </flux:card>

                @if ($this->isEditing)
                    <flux:button variant="danger" icon="trash" class="w-full" wire:click="$set('confirmingDelete', true)">
                        Delete product
                    </flux:button>
                @endif
            </div>
        </div>

        {{-- Sticky save bar --}}
        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95 lg:start-64">
            <div class="mx-auto flex max-w-7xl items-center justify-end gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <flux:button variant="ghost" :href="route('admin.products.index')" wire:navigate>Discard</flux:button>
                <flux:button
                    variant="primary"
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    :icon="null"
                >
                    <span wire:loading.remove wire:target="save">Save</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>
        </div>
    </form>

    {{-- Delete confirmation --}}
    <flux:modal wire:model="confirmingDelete" class="max-w-md">
        <flux:heading size="lg">Delete this product?</flux:heading>
        <flux:text class="mt-2">
            This product will be archived. Products with existing orders cannot be permanently removed.
        </flux:text>

        <div class="mt-6 flex justify-end gap-3">
            <flux:button variant="ghost" wire:click="$set('confirmingDelete', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="deleteProduct">Delete</flux:button>
        </div>
    </flux:modal>
</div>
