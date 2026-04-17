<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl" level="1">{{ $this->isEditing ? $product->title : 'Add product' }}</flux:heading>
        @if($this->isEditing)
            <flux:modal.trigger name="confirm-delete-product">
                <flux:button variant="danger" size="sm" icon="trash">Delete</flux:button>
            </flux:modal.trigger>
        @endif
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left Column (2/3) --}}
            <div class="space-y-6 lg:col-span-2">
                {{-- Title --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:input wire:model="title" label="Title" placeholder="Short Sleeve T-Shirt" />
                    @error('title') <flux:text class="mt-1 text-sm text-red-500">{{ $message }}</flux:text> @enderror
                </div>

                {{-- Description --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:textarea wire:model="descriptionHtml" label="Description" placeholder="Describe your product..." rows="6" />
                </div>

                {{-- Media --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="md" class="mb-4">Media</flux:heading>

                    @if($this->existingMedia->count() > 0)
                        <div class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach($this->existingMedia as $media)
                                <div class="group relative aspect-square overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    <img src="{{ Storage::url($media->storage_key) }}" alt="{{ $media->alt_text }}" class="h-full w-full object-cover" />
                                    <button type="button" wire:click="removeMedia({{ $media->id }})"
                                        class="absolute top-1 right-1 hidden rounded-full bg-red-500 p-1 text-white group-hover:block">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="rounded-lg border-2 border-dashed border-zinc-300 p-8 text-center dark:border-zinc-600">
                        <flux:icon name="arrow-up-tray" class="mx-auto mb-2 h-8 w-8 text-zinc-400" />
                        <flux:text class="text-sm text-zinc-500">Drag and drop images or click to upload</flux:text>
                        <input type="file" wire:model="newMedia" multiple accept="image/*" class="mt-2" />
                    </div>
                </div>

                {{-- Variants --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="md" class="mb-4">Variants</flux:heading>

                    {{-- Options --}}
                    @foreach($options as $optIdx => $option)
                        <div class="mb-4 flex items-start gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex-1 space-y-2">
                                <flux:input wire:model.live.debounce.500ms="options.{{ $optIdx }}.name" label="Option name" placeholder="Size" />
                                <div class="flex flex-wrap gap-2">
                                    @foreach($option['values'] as $valIdx => $val)
                                        <div class="flex items-center gap-1">
                                            <flux:input wire:model.live.debounce.500ms="options.{{ $optIdx }}.values.{{ $valIdx }}" placeholder="Value" class="w-24" />
                                            <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeOptionValue({{ $optIdx }}, {{ $valIdx }})" />
                                        </div>
                                    @endforeach
                                    <flux:button size="sm" variant="ghost" wire:click="addOptionValue({{ $optIdx }})">+ Value</flux:button>
                                </div>
                            </div>
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeOption({{ $optIdx }})" />
                        </div>
                    @endforeach

                    <div class="mb-4 flex gap-2">
                        <flux:button size="sm" variant="ghost" wire:click="addOption">+ Add another option</flux:button>
                        @if(count($options) > 0)
                            <flux:button size="sm" variant="ghost" wire:click="generateVariants">Generate variants</flux:button>
                        @endif
                    </div>

                    {{-- Variant Table --}}
                    @if(count($variants) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                                        <th class="pb-2 pr-4 font-medium text-zinc-500">Variant</th>
                                        <th class="pb-2 pr-4 font-medium text-zinc-500">SKU</th>
                                        <th class="pb-2 pr-4 font-medium text-zinc-500">Price</th>
                                        <th class="pb-2 pr-4 font-medium text-zinc-500">Compare at</th>
                                        <th class="pb-2 pr-4 font-medium text-zinc-500">Qty</th>
                                        <th class="pb-2 font-medium text-zinc-500">Ship</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($variants as $vIdx => $variant)
                                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                            <td class="py-2 pr-4 text-zinc-700 dark:text-zinc-300">
                                                {{ implode(' / ', $variant['optionValues'] ?? ['Default']) }}
                                            </td>
                                            <td class="py-2 pr-4">
                                                <input type="text" wire:model="variants.{{ $vIdx }}.sku" class="w-24 rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                                            </td>
                                            <td class="py-2 pr-4">
                                                <input type="number" wire:model="variants.{{ $vIdx }}.price" class="w-20 rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" min="0" />
                                                @error("variants.{$vIdx}.price") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                            </td>
                                            <td class="py-2 pr-4">
                                                <input type="number" wire:model="variants.{{ $vIdx }}.compareAtPrice" class="w-20 rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" min="0" />
                                            </td>
                                            <td class="py-2 pr-4">
                                                <input type="number" wire:model="variants.{{ $vIdx }}.quantity" class="w-16 rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800" min="0" />
                                            </td>
                                            <td class="py-2">
                                                <flux:checkbox wire:model="variants.{{ $vIdx }}.requiresShipping" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- SEO --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <button type="button" wire:click="$toggle('showSeo')" class="flex w-full items-center justify-between text-left">
                        <flux:heading size="md">Search engine listing</flux:heading>
                        <flux:icon :name="$showSeo ? 'chevron-up' : 'chevron-down'" class="h-5 w-5 text-zinc-400" />
                    </button>
                    @if($showSeo)
                        <div class="mt-4">
                            <flux:input wire:model="handle" label="URL handle" />
                            @error('handle') <flux:text class="mt-1 text-sm text-red-500">{{ $message }}</flux:text> @enderror
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right Column (1/3) --}}
            <div class="space-y-6">
                {{-- Status --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:select wire:model="status" label="Status">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="active">Active</flux:select.option>
                        <flux:select.option value="archived">Archived</flux:select.option>
                    </flux:select>
                </div>

                {{-- Publishing --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:input type="datetime-local" wire:model="publishedAt" label="Published at" />
                </div>

                {{-- Organization --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="space-y-4">
                        <flux:input wire:model="vendor" label="Vendor" />
                        <flux:input wire:model="productType" label="Product type" />
                        <div>
                            <flux:input wire:model="tags" label="Tags" />
                            <flux:text class="mt-1 text-xs text-zinc-500">Separate tags with commas</flux:text>
                        </div>
                    </div>
                </div>

                {{-- Collections --}}
                @if($this->availableCollections->count() > 0)
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:heading size="md" class="mb-3">Collections</flux:heading>
                        @foreach($this->availableCollections as $collection)
                            <div class="mb-2">
                                <flux:checkbox wire:model="collectionIds" :value="$collection->id" :label="$collection->title" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Sticky Save Bar --}}
        <div class="sticky bottom-0 mt-6 flex items-center justify-end gap-2 border-t border-zinc-200 bg-white px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:button variant="ghost" href="{{ route('admin.products.index') }}" wire:navigate>Discard</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </form>

    {{-- Delete Confirmation Modal --}}
    @if($this->isEditing)
        <flux:modal name="confirm-delete-product" class="md:w-96">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete this product?</flux:heading>
                    <flux:text class="mt-2">This will archive the product. It can be restored later.</flux:text>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="deleteProduct">Confirm</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
