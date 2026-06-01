<div class="pb-24">
    <x-admin.breadcrumbs :items="[
        ['label' => __('Discounts'), 'href' => route('admin.discounts.index')],
        ['label' => $this->isEditing ? ($code ?: __('Automatic')) : __('Create discount')],
    ]" />

    <flux:heading size="xl" level="1" class="mb-6">{{ $this->isEditing ? __('Edit discount') : __('Create discount') }}</flux:heading>

    <form wire:submit="save" class="mx-auto max-w-2xl space-y-6">
        {{-- Type. --}}
        <x-admin.card title="{{ __('Type') }}">
            <flux:radio.group wire:model.live="type">
                <flux:radio value="code" :label="__('Discount code')" description="{{ __('Customers enter a code at checkout') }}" />
                <flux:radio value="automatic" :label="__('Automatic discount')" description="{{ __('Applied automatically at checkout') }}" />
            </flux:radio.group>
        </x-admin.card>

        {{-- Code. --}}
        @if ($type === 'code')
            <x-admin.card title="{{ __('Discount code') }}">
                <div class="flex items-end gap-3">
                    <flux:field class="flex-1">
                        <flux:label>{{ __('Code') }}</flux:label>
                        <flux:input wire:model="code" placeholder="SUMMER20" data-test="discount-code" />
                        <flux:error name="code" />
                    </flux:field>
                    <flux:button type="button" variant="ghost" wire:click="generateCode">{{ __('Generate') }}</flux:button>
                </div>
            </x-admin.card>
        @endif

        {{-- Value. --}}
        <x-admin.card title="{{ __('Value') }}">
            <flux:radio.group wire:model.live="valueType">
                <flux:radio value="percent" :label="__('Percentage')" />
                <flux:radio value="fixed" :label="__('Fixed amount')" />
                <flux:radio value="free_shipping" :label="__('Free shipping')" />
            </flux:radio.group>

            @if ($valueType !== 'free_shipping')
                <flux:field class="mt-4">
                    <flux:label>{{ $valueType === 'percent' ? __('Percentage') : __('Amount') }}</flux:label>
                    <flux:input type="number" step="{{ $valueType === 'percent' ? '1' : '0.01' }}" wire:model="valueAmount" data-test="discount-value" />
                    <flux:error name="valueAmount" />
                </flux:field>
            @endif
        </x-admin.card>

        {{-- Conditions. --}}
        <x-admin.card title="{{ __('Conditions') }}">
            <flux:field>
                <flux:label>{{ __('Minimum purchase amount') }}</flux:label>
                <flux:input type="number" step="0.01" wire:model="minimumPurchaseAmount" placeholder="0.00" />
                <flux:description>{{ __('Leave empty for no minimum') }}</flux:description>
            </flux:field>

            <flux:field class="mt-4">
                <flux:label>{{ __('Specific products') }}</flux:label>
                <flux:input wire:model.live.debounce.300ms="productSearch" :placeholder="__('Search products...')" />
            </flux:field>
            @if ($this->productResults->isNotEmpty())
                <ul class="mt-1 rounded-lg border border-zinc-200 text-sm dark:border-zinc-700">
                    @foreach ($this->productResults as $product)
                        <li class="flex items-center justify-between px-3 py-1.5" wire:key="dp-{{ $product->id }}">
                            {{ $product->title }}
                            <flux:button size="sm" variant="ghost" wire:click="addProduct({{ $product->id }})">{{ __('Add') }}</flux:button>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($this->selectedProducts->isNotEmpty())
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($this->selectedProducts as $product)
                        <flux:badge wire:key="sp-{{ $product->id }}">{{ $product->title }}
                            <button type="button" wire:click="removeProduct({{ $product->id }})" class="ml-1">×</button>
                        </flux:badge>
                    @endforeach
                </div>
            @endif

            <flux:field class="mt-4">
                <flux:label>{{ __('Specific collections') }}</flux:label>
                <flux:input wire:model.live.debounce.300ms="collectionSearch" :placeholder="__('Search collections...')" />
            </flux:field>
            @if ($this->collectionResults->isNotEmpty())
                <ul class="mt-1 rounded-lg border border-zinc-200 text-sm dark:border-zinc-700">
                    @foreach ($this->collectionResults as $collection)
                        <li class="flex items-center justify-between px-3 py-1.5" wire:key="dc-{{ $collection->id }}">
                            {{ $collection->title }}
                            <flux:button size="sm" variant="ghost" wire:click="addCollection({{ $collection->id }})">{{ __('Add') }}</flux:button>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($this->selectedCollections->isNotEmpty())
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($this->selectedCollections as $collection)
                        <flux:badge wire:key="sc-{{ $collection->id }}">{{ $collection->title }}
                            <button type="button" wire:click="removeCollection({{ $collection->id }})" class="ml-1">×</button>
                        </flux:badge>
                    @endforeach
                </div>
            @endif
        </x-admin.card>

        {{-- Usage limits. --}}
        <x-admin.card title="{{ __('Usage limits') }}">
            <flux:field>
                <flux:label>{{ __('Total usage limit') }}</flux:label>
                <flux:input type="number" wire:model="usageLimit" placeholder="{{ __('Unlimited') }}" />
                <flux:error name="usageLimit" />
            </flux:field>
            <flux:checkbox class="mt-3" wire:model="onePerCustomer" :label="__('Limit to one use per customer')" />
        </x-admin.card>

        {{-- Active dates. --}}
        <x-admin.card title="{{ __('Active dates') }}">
            <flux:field>
                <flux:label>{{ __('Start date') }}</flux:label>
                <flux:input type="datetime-local" wire:model="startsAt" />
                <flux:error name="startsAt" />
            </flux:field>
            <flux:field class="mt-4">
                <flux:label>{{ __('End date') }}</flux:label>
                <flux:input type="datetime-local" wire:model="endsAt" />
                <flux:description>{{ __('Leave empty for no end date') }}</flux:description>
                <flux:error name="endsAt" />
            </flux:field>
        </x-admin.card>

        {{-- Status. --}}
        <x-admin.card title="{{ __('Status') }}">
            <flux:switch wire:model="isActive" :label="$isActive ? __('Active') : __('Disabled')" />
        </x-admin.card>

        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur lg:pl-72 dark:border-zinc-700 dark:bg-zinc-900/95">
            <div class="mx-auto flex max-w-2xl items-center justify-end gap-3">
                <flux:button variant="ghost" :href="route('admin.discounts.index')" wire:navigate>{{ __('Discard') }}</flux:button>
                <flux:button type="submit" variant="primary" data-test="save-discount">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </form>
</div>
