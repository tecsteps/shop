<div class="pb-24">
    <flux:heading size="xl">
        {{ $this->isEditing ? 'Edit discount' : 'Create discount' }}
    </flux:heading>

    <form wire:submit="save">
        <div class="mt-6 mx-auto max-w-3xl space-y-6">
            {{-- Type --}}
            <flux:card class="p-6">
                <flux:heading size="md">Type</flux:heading>

                <div class="mt-4 space-y-3">
                    <flux:radio wire:model="type" value="code" label="Discount code" description="Customers enter a code at checkout" />
                    <flux:radio wire:model="type" value="automatic" label="Automatic discount" description="Applied automatically at checkout" />
                </div>

                @if ($type === 'code')
                    <div class="mt-4 flex items-end gap-3">
                        <div class="flex-1">
                            <flux:field>
                                <flux:label>Discount code</flux:label>
                                <flux:input wire:model="code" placeholder="SUMMER20" />
                                <flux:error name="code" />
                            </flux:field>
                        </div>
                        <flux:button variant="ghost" wire:click="generateCode">Generate</flux:button>
                    </div>
                @endif
            </flux:card>

            {{-- Value --}}
            <flux:card class="p-6">
                <flux:heading size="md">Value</flux:heading>

                <div class="mt-4 space-y-3">
                    <flux:radio wire:model="valueType" value="percent" label="Percentage" />
                    <flux:radio wire:model="valueType" value="fixed" label="Fixed amount" />
                    <flux:radio wire:model="valueType" value="free_shipping" label="Free shipping" />
                </div>

                @if ($valueType !== 'free_shipping')
                    <div class="mt-4">
                        <flux:field>
                            <flux:label>{{ $valueType === 'percent' ? 'Percentage' : 'Amount' }}</flux:label>
                            <flux:input wire:model="valueAmount" type="number" step="0.01" min="0" placeholder="5.00" />
                            <flux:error name="valueAmount" />
                        </flux:field>
                    </div>
                @endif
            </flux:card>

            {{-- Conditions --}}
            <flux:card class="p-6">
                <flux:heading size="md">Conditions</flux:heading>

                <div class="mt-4">
                    <flux:field>
                        <flux:label>Minimum purchase amount</flux:label>
                        <flux:input wire:model="minimumPurchaseAmount" type="number" step="0.01" min="0" placeholder="0.00" />
                        <flux:description>Leave empty for no minimum</flux:description>
                    </flux:field>
                </div>

                {{-- Specific products --}}
                <div class="mt-6">
                    <flux:label>Specific products</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="productSearch"
                        icon="magnifying-glass"
                        placeholder="Search products..."
                        class="mt-2"
                    />

                    @if ($this->productSearch !== '' && $this->productResults->isNotEmpty())
                        <div class="mt-2 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                            @foreach ($this->productResults as $product)
                                <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-3 py-2 last:border-0 dark:border-zinc-800">
                                    <span class="truncate text-sm">{{ $product->title }}</span>
                                    <flux:button variant="ghost" size="sm" wire:click="addProduct({{ $product->id }})">Add</flux:button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($this->selectedProducts as $product)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                {{ $product->title }}
                                <button type="button" wire:click="removeProduct({{ $product->id }})" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-100">
                                    <flux:icon.x-mark class="size-3" />
                                </button>
                            </span>
                        @endforeach
                    </div>
                </div>

                {{-- Specific collections --}}
                <div class="mt-6">
                    <flux:label>Specific collections</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="collectionSearch"
                        icon="magnifying-glass"
                        placeholder="Search collections..."
                        class="mt-2"
                    />

                    @if ($this->collectionSearch !== '' && $this->collectionResults->isNotEmpty())
                        <div class="mt-2 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                            @foreach ($this->collectionResults as $collection)
                                <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-3 py-2 last:border-0 dark:border-zinc-800">
                                    <span class="truncate text-sm">{{ $collection->title }}</span>
                                    <flux:button variant="ghost" size="sm" wire:click="addCollection({{ $collection->id }})">Add</flux:button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($this->selectedCollections as $collection)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                {{ $collection->title }}
                                <button type="button" wire:click="removeCollection({{ $collection->id }})" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-100">
                                    <flux:icon.x-mark class="size-3" />
                                </button>
                            </span>
                        @endforeach
                    </div>
                </div>
            </flux:card>

            {{-- Usage limits --}}
            <flux:card class="p-6">
                <flux:heading size="md">Usage limits</flux:heading>

                <div class="mt-4 space-y-4">
                    <flux:field>
                        <flux:label>Total usage limit</flux:label>
                        <flux:input wire:model="usageLimit" type="number" min="1" placeholder="Unlimited" />
                    </flux:field>

                    <flux:checkbox wire:model="onePerCustomer" label="Limit to one use per customer" />
                </div>
            </flux:card>

            {{-- Active dates --}}
            <flux:card class="p-6">
                <flux:heading size="md">Active dates</flux:heading>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Start date</flux:label>
                        <flux:input type="datetime-local" wire:model="startsAt" />
                        <flux:error name="startsAt" />
                    </flux:field>
                    <flux:field>
                        <flux:label>End date</flux:label>
                        <flux:input type="datetime-local" wire:model="endsAt" />
                        <flux:description>Leave empty for no end date</flux:description>
                        <flux:error name="endsAt" />
                    </flux:field>
                </div>
            </flux:card>

            {{-- Status --}}
            <flux:card class="p-6">
                <flux:switch wire:model="isActive" label="{{ $isActive ? 'Active' : 'Disabled' }}" />
            </flux:card>
        </div>

        {{-- Sticky save bar --}}
        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95 lg:start-64">
            <div class="mx-auto flex max-w-7xl items-center justify-end gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <flux:button variant="ghost" :href="route('admin.discounts.index')" wire:navigate>Discard</flux:button>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Save</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>
        </div>
    </form>
</div>
