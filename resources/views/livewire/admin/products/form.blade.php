<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ $this->isEditing ? $title : 'Add product' }}</flux:heading>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left column --}}
            <div class="space-y-6 lg:col-span-2">
                {{-- Title --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:field>
                        <flux:label>Title</flux:label>
                        <flux:input wire:model="title" placeholder="Short Sleeve T-Shirt" />
                        <flux:error name="title" />
                    </flux:field>
                </div>

                {{-- Description --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="descriptionHtml" rows="8" placeholder="Describe your product..." />
                        <flux:error name="descriptionHtml" />
                    </flux:field>
                </div>

                {{-- Variants --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="md" class="mb-4">Variants</flux:heading>

                    {{-- Options builder --}}
                    @foreach ($options as $index => $option)
                        <div class="mb-4 flex items-end gap-4">
                            <div class="flex-1">
                                <flux:field>
                                    <flux:label>Option name</flux:label>
                                    <flux:input wire:model="options.{{ $index }}.name" placeholder="Size" wire:change="generateVariants" />
                                </flux:field>
                            </div>
                            <div class="flex-1">
                                <flux:field>
                                    <flux:label>Values (comma-separated)</flux:label>
                                    <flux:input wire:model="options.{{ $index }}.values" placeholder="S, M, L, XL" wire:change="generateVariants" />
                                </flux:field>
                            </div>
                            <flux:button variant="ghost" icon="trash" wire:click="removeOption({{ $index }})" class="mb-1" />
                        </div>
                    @endforeach

                    <flux:button variant="ghost" icon="plus" wire:click="addOption" size="sm">
                        Add another option
                    </flux:button>

                    {{-- Variants table --}}
                    @if (count($variants) > 0)
                        <div class="mt-6 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                                        <th class="pb-2 pr-4 font-medium text-zinc-500 dark:text-zinc-400">Variant</th>
                                        <th class="pb-2 pr-4 font-medium text-zinc-500 dark:text-zinc-400">SKU</th>
                                        <th class="pb-2 pr-4 font-medium text-zinc-500 dark:text-zinc-400">Price</th>
                                        <th class="pb-2 pr-4 font-medium text-zinc-500 dark:text-zinc-400">Compare at</th>
                                        <th class="pb-2 pr-4 font-medium text-zinc-500 dark:text-zinc-400">Quantity</th>
                                        <th class="pb-2 font-medium text-zinc-500 dark:text-zinc-400">Shipping</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($variants as $vIdx => $variant)
                                        <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                                            <td class="py-2 pr-4 text-zinc-900 dark:text-zinc-100">{{ $variant['label'] }}</td>
                                            <td class="py-2 pr-4"><flux:input wire:model="variants.{{ $vIdx }}.sku" size="sm" /></td>
                                            <td class="py-2 pr-4"><flux:input wire:model="variants.{{ $vIdx }}.price" type="number" step="0.01" size="sm" /></td>
                                            <td class="py-2 pr-4"><flux:input wire:model="variants.{{ $vIdx }}.compareAtPrice" type="number" step="0.01" size="sm" /></td>
                                            <td class="py-2 pr-4"><flux:input wire:model="variants.{{ $vIdx }}.quantity" type="number" size="sm" /></td>
                                            <td class="py-2"><flux:checkbox wire:model="variants.{{ $vIdx }}.requiresShipping" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- SEO --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <button type="button" wire:click="$toggle('showSeo')" class="flex w-full items-center justify-between text-left">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Search engine listing</span>
                        <flux:icon :name="$showSeo ? 'chevron-up' : 'chevron-down'" variant="mini" class="h-4 w-4 text-zinc-400" />
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
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
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
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:field>
                        <flux:label>Published at</flux:label>
                        <flux:input type="datetime-local" wire:model="publishedAt" />
                    </flux:field>
                </div>

                {{-- Organization --}}
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="md" class="mb-4">Organization</flux:heading>
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
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="md" class="mb-4">Collections</flux:heading>
                    @foreach ($this->availableCollections as $collection)
                        <div class="mb-2">
                            <flux:checkbox wire:model="collectionIds" value="{{ $collection->id }}" :label="$collection->title" />
                        </div>
                    @endforeach
                    @if ($this->availableCollections->isEmpty())
                        <flux:text class="text-sm text-zinc-400">No collections yet.</flux:text>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sticky save bar --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white px-6 py-3 dark:border-zinc-700 dark:bg-zinc-800 lg:left-64">
            <div class="flex justify-end gap-3">
                @if ($this->isEditing)
                    <flux:button variant="danger" type="button" x-on:click="$flux.modal('confirm-delete-product').show()">Delete</flux:button>
                @endif
                <flux:button variant="ghost" href="{{ route('admin.products.index') }}" wire:navigate>Discard</flux:button>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>
        </div>
    </form>

    @if ($this->isEditing)
        <flux:modal name="confirm-delete-product" class="max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">Delete this product?</flux:heading>
                <flux:text>This product will be archived. Products with existing orders cannot be permanently removed.</flux:text>
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" x-on:click="$flux.modal('confirm-delete-product').close()">Cancel</flux:button>
                    <flux:button variant="danger" wire:click="deleteProduct">Delete</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
