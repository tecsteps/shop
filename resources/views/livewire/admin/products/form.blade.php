<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ $this->isEditing ? $title : __('Add product') }}</flux:heading>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Title --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <flux:field>
                        <flux:label>{{ __('Title') }}</flux:label>
                        <flux:input wire:model="title" placeholder="{{ __('Short Sleeve T-Shirt') }}" />
                        <flux:error name="title" />
                    </flux:field>
                </div>

                {{-- Description --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <flux:field>
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model="descriptionHtml" rows="8" placeholder="{{ __('Describe your product...') }}" />
                        <flux:error name="descriptionHtml" />
                    </flux:field>
                </div>

                {{-- Variants --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <flux:heading size="md" class="mb-4">{{ __('Variants') }}</flux:heading>

                    @foreach($options as $index => $option)
                        <div class="flex gap-4 mb-4 items-end">
                            <div class="flex-1">
                                <flux:field>
                                    <flux:label>{{ __('Option name') }}</flux:label>
                                    <flux:input wire:model="options.{{ $index }}.name" placeholder="{{ __('Size') }}" wire:change="generateVariants" />
                                </flux:field>
                            </div>
                            <div class="flex-1">
                                <flux:field>
                                    <flux:label>{{ __('Values') }}</flux:label>
                                    <flux:input wire:model="options.{{ $index }}.values" placeholder="{{ __('S, M, L, XL') }}" wire:change="generateVariants" />
                                </flux:field>
                            </div>
                            <flux:button variant="ghost" size="sm" wire:click="removeOption({{ $index }})" icon="trash" />
                        </div>
                    @endforeach

                    <flux:button variant="ghost" size="sm" wire:click="addOption" icon="plus">
                        {{ __('Add another option') }}
                    </flux:button>

                    @if(count($variants) > 0)
                        <div class="mt-6 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                        <th class="text-left p-2 font-medium text-zinc-500">{{ __('Variant') }}</th>
                                        <th class="text-left p-2 font-medium text-zinc-500">{{ __('SKU') }}</th>
                                        <th class="text-left p-2 font-medium text-zinc-500">{{ __('Price') }}</th>
                                        <th class="text-left p-2 font-medium text-zinc-500">{{ __('Compare at') }}</th>
                                        <th class="text-left p-2 font-medium text-zinc-500">{{ __('Quantity') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($variants as $vIndex => $variant)
                                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                            <td class="p-2 text-zinc-700 dark:text-zinc-300">{{ $variant['optionValues'] }}</td>
                                            <td class="p-2"><flux:input wire:model="variants.{{ $vIndex }}.sku" size="sm" /></td>
                                            <td class="p-2"><flux:input wire:model="variants.{{ $vIndex }}.price" type="number" size="sm" min="0" /></td>
                                            <td class="p-2"><flux:input wire:model="variants.{{ $vIndex }}.compareAtPrice" type="number" size="sm" min="0" /></td>
                                            <td class="p-2"><flux:input wire:model="variants.{{ $vIndex }}.quantity" type="number" size="sm" min="0" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- SEO --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <flux:field>
                        <flux:label>{{ __('URL handle') }}</flux:label>
                        <flux:input wire:model="handle" placeholder="{{ __('short-sleeve-t-shirt') }}" />
                        <flux:error name="handle" />
                    </flux:field>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="space-y-6">
                {{-- Status --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <flux:field>
                        <flux:label>{{ __('Status') }}</flux:label>
                        <flux:select wire:model="status">
                            <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                            <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>

                {{-- Organization --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 space-y-4">
                    <flux:heading size="md">{{ __('Organization') }}</flux:heading>
                    <flux:field>
                        <flux:label>{{ __('Vendor') }}</flux:label>
                        <flux:input wire:model="vendor" placeholder="{{ __('Nike') }}" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Product type') }}</flux:label>
                        <flux:input wire:model="productType" placeholder="{{ __('T-Shirts') }}" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Tags') }}</flux:label>
                        <flux:input wire:model="tags" placeholder="{{ __('summer, cotton, sale') }}" />
                        <flux:description>{{ __('Separate tags with commas') }}</flux:description>
                    </flux:field>
                </div>

                {{-- Collections --}}
                @if($this->availableCollections->count() > 0)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                        <flux:heading size="md" class="mb-4">{{ __('Collections') }}</flux:heading>
                        @foreach($this->availableCollections as $collection)
                            <div class="flex items-center gap-2 py-1">
                                <flux:checkbox wire:model="collectionIds" value="{{ $collection->id }}" />
                                <flux:text>{{ $collection->title }}</flux:text>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Save Bar --}}
        <div class="sticky bottom-0 left-0 right-0 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 p-4 mt-6 flex justify-end gap-4">
            <flux:button variant="ghost" :href="route('admin.products.index')" wire:navigate>
                {{ __('Discard') }}
            </flux:button>
            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>

    @if($this->isEditing)
        <div class="mt-6">
            <flux:button variant="danger" wire:click="deleteProduct" wire:confirm="{{ __('Are you sure you want to archive this product?') }}">
                {{ __('Delete product') }}
            </flux:button>
        </div>
    @endif
</div>
