<div class="pb-24">
    <x-admin.breadcrumbs :items="[
        ['label' => __('Products'), 'href' => route('admin.products.index')],
        ['label' => $this->isEditing ? $title : __('Add product')],
    ]" />

    <flux:heading size="xl" level="1" class="mb-6">
        {{ $this->isEditing ? $title : __('Add product') }}
    </flux:heading>

    <form wire:submit="save">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left column. --}}
            <div class="space-y-6 lg:col-span-2">
                <x-admin.card>
                    <flux:field>
                        <flux:label>{{ __('Title') }}</flux:label>
                        <flux:input wire:model.blur="title" placeholder="{{ __('Short Sleeve T-Shirt') }}" data-test="product-title" />
                        <flux:error name="title" />
                    </flux:field>

                    <flux:field class="mt-4">
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model="descriptionHtml" rows="8" placeholder="{{ __('Describe your product...') }}" />
                        <flux:error name="descriptionHtml" />
                    </flux:field>
                </x-admin.card>

                {{-- Media (edit mode only; needs a persisted product). --}}
                @if ($this->isEditing)
                    <x-admin.card>
                        <livewire:admin.products.media-manager :product="$product" :key="'media-'.$product->id" />
                    </x-admin.card>
                @else
                    <x-admin.card title="{{ __('Media') }}">
                        <flux:text>{{ __('Save the product first to upload images.') }}</flux:text>
                    </x-admin.card>
                @endif

                {{-- Variants. --}}
                <x-admin.card title="{{ __('Variants') }}">
                    <div class="space-y-3">
                        @foreach ($options as $index => $option)
                            <div class="flex items-end gap-3" wire:key="option-{{ $index }}">
                                <flux:field class="flex-1">
                                    <flux:label>{{ __('Option name') }}</flux:label>
                                    <flux:input wire:model.blur="options.{{ $index }}.name" wire:change="generateVariants" placeholder="{{ __('Size') }}" />
                                </flux:field>
                                <flux:field class="flex-1">
                                    <flux:label>{{ __('Values') }}</flux:label>
                                    <flux:input wire:model.blur="options.{{ $index }}.values" wire:change="generateVariants" placeholder="{{ __('S, M, L, XL') }}" />
                                </flux:field>
                                <flux:button type="button" variant="ghost" icon="trash" wire:click="removeOption({{ $index }})" :aria-label="__('Remove option')" />
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3">
                        <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addOption" data-test="add-option">
                            {{ __('Add another option') }}
                        </flux:button>
                    </div>

                    @if (count($variants) > 0)
                        <flux:separator class="my-4" />
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                                        <th class="py-2 pr-3">{{ __('Variant') }}</th>
                                        <th class="px-2">{{ __('SKU') }}</th>
                                        <th class="px-2">{{ __('Price') }}</th>
                                        <th class="px-2">{{ __('Compare') }}</th>
                                        <th class="px-2">{{ __('Qty') }}</th>
                                        <th class="px-2">{{ __('Ship') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($variants as $index => $variant)
                                        <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="variant-{{ $index }}">
                                            <td class="py-2 pr-3 font-medium">{{ $variant['label'] }}</td>
                                            <td class="px-2"><flux:input wire:model="variants.{{ $index }}.sku" size="sm" class="w-24" /></td>
                                            <td class="px-2"><flux:input type="number" step="0.01" wire:model="variants.{{ $index }}.price" size="sm" class="w-24" data-test="variant-price-{{ $index }}" /></td>
                                            <td class="px-2"><flux:input type="number" step="0.01" wire:model="variants.{{ $index }}.compareAtPrice" size="sm" class="w-24" /></td>
                                            <td class="px-2"><flux:input type="number" wire:model="variants.{{ $index }}.quantity" size="sm" class="w-20" /></td>
                                            <td class="px-2"><flux:checkbox wire:model="variants.{{ $index }}.requiresShipping" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-admin.card>

                {{-- SEO. --}}
                <x-admin.card>
                    <div x-data="{ open: false }">
                        <button type="button" x-on:click="open = !open" class="flex items-center gap-2 text-sm font-medium" x-bind:aria-expanded="open">
                            <flux:icon.chevron-right class="size-4 transition" x-bind:class="open && 'rotate-90'" />
                            {{ __('Search engine listing') }}
                        </button>
                        <div x-show="open" x-collapse class="mt-4">
                            <flux:field>
                                <flux:label>{{ __('URL handle') }}</flux:label>
                                <flux:input wire:model="handle" placeholder="short-sleeve-t-shirt" />
                                <flux:error name="handle" />
                            </flux:field>
                        </div>
                    </div>
                </x-admin.card>
            </div>

            {{-- Right column. --}}
            <div class="space-y-6">
                <x-admin.card title="{{ __('Status') }}">
                    <flux:select wire:model="status" data-test="product-status">
                        <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                        <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                        <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                    </flux:select>
                </x-admin.card>

                <x-admin.card title="{{ __('Publishing') }}">
                    <flux:field>
                        <flux:label>{{ __('Published at') }}</flux:label>
                        <flux:input type="datetime-local" wire:model="publishedAt" />
                    </flux:field>
                </x-admin.card>

                <x-admin.card title="{{ __('Organization') }}">
                    <flux:field>
                        <flux:label>{{ __('Vendor') }}</flux:label>
                        <flux:input wire:model="vendor" placeholder="Nike" />
                    </flux:field>
                    <flux:field class="mt-4">
                        <flux:label>{{ __('Product type') }}</flux:label>
                        <flux:input wire:model="productType" placeholder="T-Shirts" />
                    </flux:field>
                    <flux:field class="mt-4">
                        <flux:label>{{ __('Tags') }}</flux:label>
                        <flux:input wire:model="tags" placeholder="summer, cotton, sale" />
                        <flux:description>{{ __('Separate tags with commas') }}</flux:description>
                    </flux:field>
                </x-admin.card>

                <x-admin.card title="{{ __('Collections') }}">
                    @forelse ($this->availableCollections as $collection)
                        <flux:checkbox
                            wire:model="collectionIds"
                            :value="$collection->id"
                            :label="$collection->title"
                            wire:key="collection-{{ $collection->id }}"
                        />
                    @empty
                        <flux:text>{{ __('No collections yet.') }}</flux:text>
                    @endforelse
                </x-admin.card>

                @if ($this->isEditing)
                    <x-admin.card>
                        <flux:button type="button" variant="danger" icon="trash" wire:click="$set('showDeleteModal', true)" class="w-full" data-test="delete-product">
                            {{ __('Archive product') }}
                        </flux:button>
                    </x-admin.card>
                @endif
            </div>
        </div>

        {{-- Sticky save bar. --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur lg:pl-72 dark:border-zinc-700 dark:bg-zinc-900/95">
            <div class="mx-auto flex max-w-5xl items-center justify-end gap-3">
                <flux:button variant="ghost" :href="route('admin.products.index')" wire:navigate>{{ __('Discard') }}</flux:button>
                <flux:button type="submit" variant="primary" data-test="save-product">
                    <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </flux:button>
            </div>
        </div>
    </form>

    {{-- Delete confirmation modal. --}}
    @if ($this->isEditing)
        <flux:modal wire:model.self="showDeleteModal" name="confirm-delete-product" class="md:w-96">
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Delete this product?') }}</flux:heading>
                <flux:text>{{ __('This product will be archived. Products with existing orders cannot be permanently removed.') }}</flux:text>
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="danger" wire:click="deleteProduct">{{ __('Archive') }}</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
