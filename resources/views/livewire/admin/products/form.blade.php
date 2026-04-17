<div>
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('admin.products.index') }}" wire:navigate>Products</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $this->isEditing ? $title : 'Add product' }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <flux:heading size="xl" class="mb-6">{{ $this->isEditing ? $title : 'Add product' }}</flux:heading>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left Column --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Title --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model.live.debounce.500ms="title" placeholder="Short Sleeve T-Shirt" />
                    <flux:error name="title" />
                </flux:field>
            </div>

            {{-- Description --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="descriptionHtml" rows="8" placeholder="Describe your product..." />
                </flux:field>
            </div>

            {{-- Variants --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:heading size="md" class="mb-4">Variants</flux:heading>

                {{-- Options builder --}}
                @foreach ($options as $index => $option)
                    <div class="mb-4 flex items-end gap-3" wire:key="option-{{ $index }}">
                        <div class="flex-1">
                            <flux:field>
                                <flux:label>Option name</flux:label>
                                <flux:input wire:model="options.{{ $index }}.name" placeholder="Size" />
                            </flux:field>
                        </div>
                        <div class="flex-1">
                            <flux:field>
                                <flux:label>Values</flux:label>
                                <flux:input wire:model.blur="options.{{ $index }}.values" placeholder="S, M, L, XL" wire:change="generateVariants" />
                            </flux:field>
                        </div>
                        <flux:button variant="ghost" icon="trash" wire:click="removeOption({{ $index }})" class="text-red-500" />
                    </div>
                @endforeach

                <flux:button variant="ghost" wire:click="addOption" size="sm" class="mb-4">
                    <flux:icon name="plus" variant="mini" class="mr-1 h-4 w-4" />
                    Add another option
                </flux:button>

                <flux:separator class="my-4" />

                {{-- Variants table --}}
                @if (count($variants) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="pb-2 pr-3 font-medium text-gray-500">Variant</th>
                                    <th class="pb-2 pr-3 font-medium text-gray-500">SKU</th>
                                    <th class="pb-2 pr-3 font-medium text-gray-500">Price</th>
                                    <th class="pb-2 pr-3 font-medium text-gray-500">Compare at</th>
                                    <th class="pb-2 pr-3 font-medium text-gray-500">Quantity</th>
                                    <th class="pb-2 font-medium text-gray-500">Ship</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($variants as $vIndex => $variant)
                                    <tr class="border-b border-gray-100 dark:border-gray-800" wire:key="variant-{{ $vIndex }}">
                                        <td class="py-2 pr-3 text-sm">{{ $variant['optionValues'] }}</td>
                                        <td class="py-2 pr-3">
                                            <flux:input wire:model="variants.{{ $vIndex }}.sku" size="sm" placeholder="SKU" />
                                        </td>
                                        <td class="py-2 pr-3">
                                            <flux:input wire:model="variants.{{ $vIndex }}.price" type="number" step="0.01" size="sm" />
                                        </td>
                                        <td class="py-2 pr-3">
                                            <flux:input wire:model="variants.{{ $vIndex }}.compareAtPrice" type="number" step="0.01" size="sm" />
                                        </td>
                                        <td class="py-2 pr-3">
                                            <flux:input wire:model="variants.{{ $vIndex }}.quantity" type="number" size="sm" />
                                        </td>
                                        <td class="py-2">
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
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900" x-data="{ open: false }">
                <button @click="open = !open" class="flex w-full items-center justify-between text-left">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Search engine listing</span>
                    <span x-show="open"><flux:icon name="chevron-up" variant="mini" class="h-4 w-4 text-gray-400" /></span>
                    <span x-show="!open"><flux:icon name="chevron-down" variant="mini" class="h-4 w-4 text-gray-400" /></span>
                </button>
                <div x-show="open" x-transition class="mt-4">
                    <flux:field>
                        <flux:label>URL handle</flux:label>
                        <flux:input wire:model="handle" placeholder="short-sleeve-t-shirt" />
                        <flux:error name="handle" />
                    </flux:field>
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- Status --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="archived">Archived</option>
                    </flux:select>
                </flux:field>
            </div>

            {{-- Publishing --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:field>
                    <flux:label>Published at</flux:label>
                    <flux:input type="datetime-local" wire:model="publishedAt" />
                </flux:field>
            </div>

            {{-- Organization --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <div class="space-y-4">
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
            </div>

            {{-- Collections --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <flux:heading size="md" class="mb-3">Collections</flux:heading>
                @foreach ($this->availableCollections as $collection)
                    <div class="mb-2" wire:key="col-{{ $collection->id }}">
                        <flux:checkbox
                            wire:model="collectionIds"
                            value="{{ $collection->id }}"
                            label="{{ $collection->title }}"
                        />
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Sticky Save Bar --}}
    <div class="fixed bottom-0 left-0 right-0 z-30 border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900 lg:left-64">
        <div class="flex items-center justify-end gap-3">
            @if ($this->isEditing)
                <flux:button variant="ghost" wire:click="deleteProduct" wire:confirm="Are you sure you want to archive this product?">
                    Delete
                </flux:button>
            @endif
            <flux:button variant="ghost" href="{{ route('admin.products.index') }}" wire:navigate>Discard</flux:button>
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </div>
</div>
