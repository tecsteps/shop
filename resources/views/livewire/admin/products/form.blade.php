<div>
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('admin.products.index') }}" wire:navigate>Products</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $isEditing ? $this->product->title : 'Add product' }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">{{ $isEditing ? $this->product->title : 'Add product' }}</flux:heading>
        @if ($isEditing)
            @can('delete', $this->product)
                <flux:button variant="danger" wire:click="$set('showDeleteModal', true)">Delete</flux:button>
            @endcan
        @endif
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="flex flex-col gap-4 lg:col-span-2">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="title" placeholder="Short Sleeve T-Shirt" />
                    <flux:error name="title" />
                </flux:field>
                <flux:field class="mt-4">
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="descriptionHtml" rows="8" placeholder="Describe your product..." />
                    <flux:error name="descriptionHtml" />
                </flux:field>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Media</flux:heading>
                <label class="mt-4 flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 px-6 py-8 text-center dark:border-zinc-600" for="product-media-upload">
                    <flux:icon.arrow-up-tray class="size-8 text-zinc-400" variant="outline" />
                    <span class="mt-2 text-sm">Drag and drop images or click to upload</span>
                    <input id="product-media-upload" type="file" wire:model="newMedia" multiple accept="image/*" class="sr-only" />
                </label>
                <div wire:loading wire:target="newMedia" class="mt-2 h-2 rounded bg-zinc-200 dark:bg-zinc-700">
                    <div class="h-2 animate-pulse rounded bg-zinc-500"></div>
                </div>
                @if (count($this->media) > 0)
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($this->media as $item)
                            <div class="group relative aspect-square overflow-hidden rounded-lg" wire:key="media-{{ $item['id'] }}">
                                <img src="{{ $item['url'] }}" alt="{{ $item['alt_text'] }}" class="size-full object-cover" />
                                <div class="absolute inset-0 flex items-start justify-end gap-1 bg-zinc-950/0 p-2 opacity-0 transition group-hover:bg-zinc-950/30 group-hover:opacity-100">
                                    <flux:button variant="ghost" size="sm" wire:click="removeMedia({{ $item['id'] }})" aria-label="Remove image">
                                        <flux:icon.x-mark class="size-4" variant="outline" />
                                    </flux:button>
                                </div>
                                <flux:input wire:change="updateMediaAlt({{ $item['id'] }}, $event.target.value)" value="{{ $item['alt_text'] }}" placeholder="Alt text" aria-label="Alt text" class="absolute bottom-1 left-1 right-1 text-xs" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Variants</flux:heading>
                @foreach ($this->options as $index => $option)
                    <div class="mt-4 flex flex-wrap items-end gap-2" wire:key="option-{{ $index }}">
                        <flux:input wire:model.live.debounce.500ms="options.{{ $index }}.name" label="Option name" placeholder="Size" class="w-40" />
                        <flux:input wire:model.live.debounce.500ms="options.{{ $index }}.values" label="Values" placeholder="S, M, L, XL" class="min-w-52 flex-1" />
                        <flux:button variant="ghost" size="sm" wire:click="removeOption({{ $index }})" aria-label="Remove option">
                            <flux:icon.trash class="size-4" variant="outline" />
                        </flux:button>
                    </div>
                @endforeach
                <flux:button variant="ghost" size="sm" wire:click="addOption" class="mt-3">
                    <flux:icon.plus class="size-4" variant="outline" /> Add another option
                </flux:button>

                @if (count($this->variants) > 0)
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                                    <th class="pb-2 pr-2 font-medium">Variant</th>
                                    <th class="pb-2 pr-2 font-medium">SKU</th>
                                    <th class="pb-2 pr-2 font-medium">Price</th>
                                    <th class="pb-2 pr-2 font-medium">Compare at</th>
                                    <th class="pb-2 pr-2 font-medium">Qty</th>
                                    <th class="pb-2 font-medium">Ships</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->variants as $index => $variant)
                                    <tr wire:key="variant-{{ $index }}">
                                        <td class="py-2 pr-2">{{ $variant['label'] }}</td>
                                        <td class="py-2 pr-2"><flux:input wire:model="variants.{{ $index }}.sku" size="sm" class="w-28" aria-label="SKU" /></td>
                                        <td class="py-2 pr-2"><flux:input wire:model="variants.{{ $index }}.price" type="number" step="0.01" min="0" size="sm" class="w-24" aria-label="Price" /></td>
                                        <td class="py-2 pr-2"><flux:input wire:model="variants.{{ $index }}.compare_at" type="number" step="0.01" min="0" size="sm" class="w-24" aria-label="Compare at price" /></td>
                                        <td class="py-2 pr-2"><flux:input wire:model="variants.{{ $index }}.quantity" type="number" min="0" size="sm" class="w-20" aria-label="Quantity" /></td>
                                        <td class="py-2"><flux:checkbox wire:model="variants.{{ $index }}.requires_shipping" aria-label="Requires shipping" /></td>
                                    </tr>
                                    @error("variants.{$index}.price") <tr><td colspan="6" class="text-sm text-red-600">{{ $message }}</td></tr> @enderror
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <button type="button" wire:click="$toggle('seoOpen')" class="flex items-center gap-2" aria-expanded="{{ $this->seoOpen ? 'true' : 'false' }}">
                    <flux:icon.chevron-down class="size-4 transition-transform {{ $this->seoOpen ? 'rotate-180' : '' }}" variant="outline" />
                    <span class="text-sm font-medium">Search engine listing</span>
                </button>
                @if ($this->seoOpen)
                    <flux:field class="mt-4">
                        <flux:label>URL handle</flux:label>
                        <flux:input wire:model="handle" placeholder="short-sleeve-t-shirt" />
                        <flux:error name="handle" />
                    </flux:field>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="archived">Archived</flux:select.option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>
                <flux:field class="mt-4">
                    <flux:label>Published at</flux:label>
                    <flux:input wire:model="publishedAt" type="datetime-local" />
                    <flux:error name="publishedAt" />
                </flux:field>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Organization</flux:heading>
                <flux:input wire:model="vendor" label="Vendor" placeholder="Nike" class="mt-4" />
                <flux:input wire:model="productType" label="Product type" placeholder="T-Shirts" class="mt-4" />
                <flux:field class="mt-4">
                    <flux:label>Tags</flux:label>
                    <flux:input wire:model="tags" placeholder="summer, cotton, sale" />
                    <flux:description>Separate tags with commas</flux:description>
                </flux:field>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Collections</flux:heading>
                <div class="mt-3 flex max-h-56 flex-col gap-2 overflow-y-auto">
                    @foreach ($availableCollections as $collection)
                        <label class="flex items-center gap-2 text-sm" wire:key="collection-{{ $collection->getKey() }}">
                            <flux:checkbox wire:model="collectionIds" value="{{ $collection->getKey() }}" />
                            <span>{{ $collection->title }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="sticky bottom-0 z-10 mt-6 flex justify-end gap-2 border-t border-zinc-200 bg-zinc-100 py-4 dark:border-zinc-700 dark:bg-zinc-950">
        <flux:button variant="ghost" href="{{ route('admin.products.index') }}" wire:navigate>Discard</flux:button>
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading wire:target="save">Saving...</span>
        </flux:button>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <flux:heading size="lg">Delete this product?</flux:heading>
        <flux:text class="mt-2">This product will be archived. Products with existing orders cannot be permanently removed.</flux:text>
        <div class="mt-4 flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancel</flux:button>
            <flux:button variant="danger" wire:click="deleteProduct">Delete</flux:button>
        </div>
    </flux:modal>
</div>
