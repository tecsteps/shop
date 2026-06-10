<div class="space-y-6 pb-24">
    <x-admin.breadcrumbs :items="[
        ['label' => __('Discounts'), 'href' => route('admin.discounts.index')],
        ['label' => $this->isEditing ? ($discount->code ?? __('Automatic discount')) : __('Create discount')],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="1">
            {{ $this->isEditing ? ($discount->code ?? __('Automatic discount')) : __('Create discount') }}
        </flux:heading>

        @if ($this->isEditing)
            @can('delete', $discount)
                <flux:modal.trigger name="confirm-delete-discount">
                    <flux:button variant="danger" data-test="delete-discount-button">{{ __('Delete') }}</flux:button>
                </flux:modal.trigger>
            @endcan
        @endif
    </div>

    <form wire:submit="save" class="mx-auto w-full max-w-3xl space-y-6">
        {{-- Type --}}
        <x-admin.card :heading="__('Discount type')">
            <flux:radio.group wire:model.live="type">
                <flux:radio value="code" :label="__('Discount code')" :description="__('Customers enter a code at checkout')" data-test="discount-type-code" />
                <flux:radio value="automatic" :label="__('Automatic discount')" :description="__('Applied automatically at checkout')" data-test="discount-type-automatic" />
            </flux:radio.group>
            <flux:error name="type" />
        </x-admin.card>

        {{-- Code --}}
        @if ($type === 'code')
            <x-admin.card :heading="__('Discount code')">
                <div class="flex items-end gap-3">
                    <flux:field class="flex-1">
                        <flux:label>{{ __('Code') }}</flux:label>
                        <flux:input wire:model="code" placeholder="SUMMER20" data-test="discount-code-input" />
                    </flux:field>
                    <flux:button variant="ghost" wire:click="generateCode" data-test="generate-code-button">
                        {{ __('Generate') }}
                    </flux:button>
                </div>
                <flux:error name="code" />
            </x-admin.card>
        @endif

        {{-- Value --}}
        <x-admin.card :heading="__('Value')" class="space-y-4">
            <flux:radio.group wire:model.live="valueType">
                <flux:radio value="percent" :label="__('Percentage')" data-test="value-type-percent" />
                <flux:radio value="fixed" :label="__('Fixed amount')" data-test="value-type-fixed" />
                <flux:radio value="free_shipping" :label="__('Free shipping')" data-test="value-type-free-shipping" />
            </flux:radio.group>
            <flux:error name="valueType" />

            @if ($valueType !== 'free_shipping')
                <flux:field class="max-w-48">
                    <flux:label>{{ $valueType === 'percent' ? __('Percentage') : __('Amount') }}</flux:label>
                    <flux:input
                        wire:model="valueAmount"
                        type="number"
                        step="{{ $valueType === 'percent' ? '1' : '0.01' }}"
                        min="0"
                        @if ($valueType === 'percent') max="100" @endif
                        placeholder="{{ $valueType === 'percent' ? '10' : '5.00' }}"
                        data-test="discount-value-input"
                    />
                    <flux:error name="valueAmount" />
                </flux:field>
            @endif
        </x-admin.card>

        {{-- Conditions --}}
        <x-admin.card :heading="__('Conditions')" class="space-y-5">
            <flux:field class="max-w-48">
                <flux:label>{{ __('Minimum purchase amount') }}</flux:label>
                <flux:input wire:model="minimumPurchaseAmount" type="number" step="0.01" min="0" placeholder="0.00" data-test="minimum-purchase-input" />
                <flux:description>{{ __('Leave empty for no minimum') }}</flux:description>
                <flux:error name="minimumPurchaseAmount" />
            </flux:field>

            <flux:separator />

            <div class="space-y-2">
                <flux:label>{{ __('Specific products') }}</flux:label>
                <div class="relative">
                    <flux:input
                        wire:model.live.debounce.300ms="productSearch"
                        icon="magnifying-glass"
                        :placeholder="__('Search products...')"
                        data-test="discount-product-search"
                    />

                    @if ($this->productSearchResults->isNotEmpty())
                        <div class="absolute inset-x-0 top-full z-20 mt-1 max-h-56 overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                            @foreach ($this->productSearchResults as $product)
                                <button
                                    type="button"
                                    wire:key="discount-product-result-{{ $product->id }}"
                                    wire:click="addProduct({{ $product->id }})"
                                    class="flex w-full cursor-pointer items-center px-3 py-2 text-left text-sm text-zinc-800 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-700/50"
                                >
                                    {{ $product->title }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($this->selectedProducts->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->selectedProducts as $product)
                            <flux:badge wire:key="selected-product-{{ $product->id }}" color="zinc">
                                {{ $product->title }}
                                <button
                                    type="button"
                                    wire:click="removeProduct({{ $product->id }})"
                                    class="ml-1 cursor-pointer"
                                    aria-label="{{ __('Remove :title', ['title' => $product->title]) }}"
                                >
                                    <flux:icon name="x-mark" variant="micro" />
                                </button>
                            </flux:badge>
                        @endforeach
                    </div>
                @endif
                <flux:description>{{ __('Leave empty to apply to the entire order.') }}</flux:description>
            </div>

            <div class="space-y-2">
                <flux:label>{{ __('Specific collections') }}</flux:label>
                <div class="relative">
                    <flux:input
                        wire:model.live.debounce.300ms="collectionSearch"
                        icon="magnifying-glass"
                        :placeholder="__('Search collections...')"
                        data-test="discount-collection-search"
                    />

                    @if ($this->collectionSearchResults->isNotEmpty())
                        <div class="absolute inset-x-0 top-full z-20 mt-1 max-h-56 overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                            @foreach ($this->collectionSearchResults as $collection)
                                <button
                                    type="button"
                                    wire:key="discount-collection-result-{{ $collection->id }}"
                                    wire:click="addCollection({{ $collection->id }})"
                                    class="flex w-full cursor-pointer items-center px-3 py-2 text-left text-sm text-zinc-800 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-700/50"
                                >
                                    {{ $collection->title }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($this->selectedCollections->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->selectedCollections as $collection)
                            <flux:badge wire:key="selected-collection-{{ $collection->id }}" color="zinc">
                                {{ $collection->title }}
                                <button
                                    type="button"
                                    wire:click="removeCollection({{ $collection->id }})"
                                    class="ml-1 cursor-pointer"
                                    aria-label="{{ __('Remove :title', ['title' => $collection->title]) }}"
                                >
                                    <flux:icon name="x-mark" variant="micro" />
                                </button>
                            </flux:badge>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-admin.card>

        {{-- Usage limits --}}
        <x-admin.card :heading="__('Usage limits')" class="space-y-4">
            <flux:field class="max-w-48">
                <flux:label>{{ __('Total usage limit') }}</flux:label>
                <flux:input wire:model="usageLimit" type="number" min="1" :placeholder="__('Unlimited')" data-test="usage-limit-input" />
                <flux:error name="usageLimit" />
            </flux:field>

            <flux:checkbox wire:model="onePerCustomer" :label="__('Limit to one use per customer')" data-test="one-per-customer-checkbox" />
        </x-admin.card>

        {{-- Active dates --}}
        <x-admin.card :heading="__('Active dates')" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Start date') }}</flux:label>
                    <flux:input wire:model="startsAt" type="datetime-local" data-test="starts-at-input" />
                    <flux:error name="startsAt" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('End date') }}</flux:label>
                    <flux:input wire:model="endsAt" type="datetime-local" data-test="ends-at-input" />
                    <flux:description>{{ __('Leave empty for no end date') }}</flux:description>
                    <flux:error name="endsAt" />
                </flux:field>
            </div>
        </x-admin.card>

        {{-- Status --}}
        <x-admin.card :heading="__('Status')">
            <flux:field variant="inline">
                <flux:switch wire:model.live="isActive" data-test="discount-active-switch" />
                <flux:label>{{ $isActive ? __('Active') : __('Disabled') }}</flux:label>
            </flux:field>
        </x-admin.card>

        {{-- Sticky save bar --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 backdrop-blur lg:pl-64 dark:border-zinc-700 dark:bg-zinc-900/95">
            <div class="mx-auto flex max-w-7xl items-center justify-end gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <flux:button variant="ghost" :href="route('admin.discounts.index')" wire:navigate>
                    {{ __('Discard') }}
                </flux:button>
                <flux:button type="submit" variant="primary" data-test="save-discount-button">
                    <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
                </flux:button>
            </div>
        </div>
    </form>

    @if ($this->isEditing)
        <flux:modal name="confirm-delete-discount" class="md:max-w-md">
            <div class="space-y-4">
                <flux:heading size="lg">{{ __('Delete this discount?') }}</flux:heading>
                <flux:text>{{ __('The discount will be permanently removed. Existing orders are not affected.') }}</flux:text>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="deleteDiscount" data-test="confirm-delete-discount-button">
                        {{ __('Delete discount') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
