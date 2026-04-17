<div>
    {{-- Breadcrumbs --}}
    <div class="mb-4">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.products.index')" wire:navigate>Products</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $this->isEditing ? $title : 'Add product' }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ $this->isEditing ? $title : 'Add product' }}</flux:heading>
        @if ($this->isEditing)
            <flux:button variant="danger" size="sm" wire:click="$set('showDeleteModal', true)">
                Delete product
            </flux:button>
        @endif
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left column --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Title and Description --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 space-y-4">
                    <flux:field>
                        <flux:label>Title</flux:label>
                        <flux:input wire:model="title" placeholder="Short Sleeve T-Shirt" />
                        <flux:error name="title" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="descriptionHtml" rows="8" placeholder="Describe your product..." />
                        <flux:error name="descriptionHtml" />
                    </flux:field>
                </div>

                {{-- Media --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <flux:heading size="md" class="mb-4">Media</flux:heading>

                    @if (count($existingMedia) > 0)
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-4">
                            @foreach ($existingMedia as $media)
                                <div wire:key="media-{{ $media['id'] }}" class="relative group aspect-square">
                                    <img
                                        src="{{ $media['url'] }}"
                                        alt="{{ $media['alt_text'] }}"
                                        class="w-full h-full object-cover rounded-lg"
                                    >
                                    <button
                                        type="button"
                                        wire:click="removeMedia({{ $media['id'] }})"
                                        class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <flux:icon name="x-mark" class="size-3" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg p-8 text-center">
                        <flux:icon name="photo" class="size-10 mx-auto text-zinc-400 mb-2" />
                        <flux:text class="text-zinc-500 dark:text-zinc-400 mb-2">Drag and drop images or click to upload</flux:text>
                        <input
                            type="file"
                            wire:model="newMedia"
                            multiple
                            accept="image/*"
                            class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-700 dark:file:text-zinc-300"
                        >
                    </div>

                    <div wire:loading wire:target="newMedia" class="mt-2">
                        <div class="h-1 bg-zinc-200 dark:bg-zinc-700 rounded overflow-hidden">
                            <div class="h-full bg-blue-500 rounded animate-pulse" style="width: 50%"></div>
                        </div>
                    </div>
                </div>

                {{-- Variants --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <flux:heading size="md" class="mb-4">Variants</flux:heading>

                    {{-- Options builder --}}
                    @foreach ($options as $index => $option)
                        <div wire:key="option-{{ $index }}" class="flex gap-3 mb-3 items-end">
                            <div class="flex-1">
                                <flux:field>
                                    <flux:label>Option name</flux:label>
                                    <flux:input wire:model.blur="options.{{ $index }}.name" placeholder="Size" />
                                </flux:field>
                            </div>
                            <div class="flex-[2]">
                                <flux:field>
                                    <flux:label>Values (comma-separated)</flux:label>
                                    <flux:input wire:model.blur="options.{{ $index }}.values" wire:change="generateVariants" placeholder="S, M, L, XL" />
                                </flux:field>
                            </div>
                            <flux:button variant="ghost" icon="trash" wire:click="removeOption({{ $index }})" class="shrink-0" />
                        </div>
                    @endforeach

                    <flux:button variant="ghost" size="sm" icon="plus" wire:click="addOption" class="mb-4">
                        Add another option
                    </flux:button>

                    <flux:separator class="my-4" />

                    {{-- Variants table --}}
                    @if (count($variants) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                        <th class="text-left px-3 py-2 font-medium text-zinc-500 dark:text-zinc-400">Variant</th>
                                        <th class="text-left px-3 py-2 font-medium text-zinc-500 dark:text-zinc-400">SKU</th>
                                        <th class="text-left px-3 py-2 font-medium text-zinc-500 dark:text-zinc-400">Price</th>
                                        <th class="text-left px-3 py-2 font-medium text-zinc-500 dark:text-zinc-400">Compare at</th>
                                        <th class="text-left px-3 py-2 font-medium text-zinc-500 dark:text-zinc-400">Qty</th>
                                        <th class="text-left px-3 py-2 font-medium text-zinc-500 dark:text-zinc-400">Ship</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach ($variants as $vIndex => $variant)
                                        <tr wire:key="variant-{{ $vIndex }}">
                                            <td class="px-3 py-2 text-zinc-900 dark:text-white font-medium">
                                                {{ $variant['title'] }}
                                            </td>
                                            <td class="px-3 py-2">
                                                <input
                                                    type="text"
                                                    wire:model="variants.{{ $vIndex }}.sku"
                                                    class="w-24 px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white"
                                                >
                                            </td>
                                            <td class="px-3 py-2">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    wire:model="variants.{{ $vIndex }}.price"
                                                    class="w-20 px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white"
                                                >
                                                @error("variants.{$vIndex}.price")
                                                    <span class="text-xs text-red-500">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="px-3 py-2">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    wire:model="variants.{{ $vIndex }}.compareAtPrice"
                                                    class="w-20 px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white"
                                                >
                                            </td>
                                            <td class="px-3 py-2">
                                                <input
                                                    type="number"
                                                    wire:model="variants.{{ $vIndex }}.quantity"
                                                    class="w-16 px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white"
                                                >
                                            </td>
                                            <td class="px-3 py-2">
                                                <flux:checkbox wire:model="variants.{{ $vIndex }}.requiresShipping" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- SEO --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <button
                        type="button"
                        wire:click="$toggle('showSeo')"
                        class="flex items-center gap-2 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                    >
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
                </div>
            </div>

            {{-- Right column --}}
            <div class="space-y-6">
                {{-- Status --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="draft">Draft</flux:select.option>
                            <flux:select.option value="active">Active</flux:select.option>
                            <flux:select.option value="archived">Archived</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>

                {{-- Publishing --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <flux:field>
                        <flux:label>Published at</flux:label>
                        <flux:input type="datetime-local" wire:model="publishedAt" />
                    </flux:field>
                </div>

                {{-- Organization --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 space-y-4">
                    <flux:heading size="md">Organization</flux:heading>
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

                {{-- Collections --}}
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <flux:heading size="md" class="mb-3">Collections</flux:heading>
                    @if ($this->availableCollections->count() > 0)
                        <div class="space-y-2">
                            @foreach ($this->availableCollections as $collection)
                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                    <flux:checkbox wire:model="collectionIds" value="{{ $collection->id }}" />
                                    <span class="text-zinc-700 dark:text-zinc-300">{{ $collection->title }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm">No collections yet.</flux:text>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sticky save bar --}}
        <div class="fixed bottom-0 left-0 lg:left-64 right-0 bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 px-6 py-3 flex justify-end gap-3 z-10">
            <flux:button variant="ghost" :href="route('admin.products.index')" wire:navigate>
                Discard
            </flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>

        {{-- Bottom spacing for sticky bar --}}
        <div class="h-20"></div>
    </form>

    {{-- Delete confirmation modal --}}
    @if ($this->isEditing)
        <flux:modal name="confirm-delete-product" :show="$showDeleteModal" class="max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">Delete this product?</flux:heading>
                <flux:text>This product will be archived. Products with existing orders cannot be permanently removed.</flux:text>
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" @click="$wire.showDeleteModal = false">Cancel</flux:button>
                    <flux:button variant="danger" wire:click="deleteProduct">Delete</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
