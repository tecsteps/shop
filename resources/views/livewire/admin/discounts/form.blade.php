<div class="space-y-6 pb-20">
    <div class="flex flex-wrap items-center gap-3">
        <flux:heading size="xl">{{ $this->isEditing() ? ($discount->code ?? 'Automatic discount') : 'Create discount' }}</flux:heading>
        @if ($this->isEditing())
            <flux:badge size="sm" :color="match ($discount->status) {
                \App\Enums\DiscountStatus::Active => 'green',
                \App\Enums\DiscountStatus::Expired => 'red',
                default => 'zinc',
            }">{{ Str::headline($discount->status->value) }}</flux:badge>
        @endif
    </div>

    <div class="space-y-6">
        {{-- Type section --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">Type</flux:heading>
            <flux:radio.group wire:model.live="type" class="mt-3">
                <flux:radio value="code" label="Discount code" description="Customers enter a code at checkout" />
                <flux:radio value="automatic" label="Automatic discount" description="Applied automatically at checkout" />
            </flux:radio.group>
            <flux:error name="type" />
        </div>

        {{-- Code section (code type only) --}}
        @if ($type === 'code')
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="md">Code</flux:heading>
                <flux:field class="mt-3">
                    <flux:label for="code">Discount code</flux:label>
                    <div class="flex items-center gap-2">
                        <flux:input id="code" wire:model.blur="code" placeholder="SUMMER20" class="w-full uppercase" />
                        <flux:button variant="ghost" wire:click="generateCode">Generate</flux:button>
                    </div>
                    <flux:error name="code" />
                </flux:field>
            </div>
        @endif

        {{-- Value section --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">Value</flux:heading>
            <flux:radio.group wire:model.live="valueType" class="mt-3">
                <flux:radio value="percent" label="Percentage" />
                <flux:radio value="fixed" label="Fixed amount" />
                <flux:radio value="free_shipping" label="Free shipping" />
            </flux:radio.group>
            <flux:error name="valueType" />

            @if ($valueType !== 'free_shipping')
                <flux:field class="mt-4">
                    <flux:label for="valueAmount">{{ $valueType === 'percent' ? 'Percentage' : 'Amount (cents)' }}</flux:label>
                    <flux:input id="valueAmount" type="number" min="1" @if ($valueType === 'percent') max="100" @endif wire:model.blur="valueAmount" placeholder="{{ $valueType === 'percent' ? '10' : '500' }}" />
                    @if ($valueType === 'percent')
                        <flux:description>Whole percentage, 1–100.</flux:description>
                    @else
                        <flux:description>Amount in cents (e.g. 500 = 5.00).</flux:description>
                    @endif
                    <flux:error name="valueAmount" />
                </flux:field>
            @endif
        </div>

        {{-- Conditions section --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">Conditions</flux:heading>

            <flux:field class="mt-4">
                <flux:label for="minimumPurchaseAmount">Minimum purchase amount (cents)</flux:label>
                <flux:input id="minimumPurchaseAmount" type="number" min="0" wire:model.blur="minimumPurchaseAmount" placeholder="0" />
                <flux:description>Leave empty for no minimum</flux:description>
                <flux:error name="minimumPurchaseAmount" />
            </flux:field>

            {{-- Specific products picker --}}
            <flux:field class="mt-4">
                <flux:label for="productSearch">Specific products</flux:label>
                <flux:input id="productSearch" icon="magnifying-glass" wire:model.live.debounce.300ms="productSearch" placeholder="Search products..." />
                <flux:description>Leave empty to apply to all products</flux:description>
                <flux:error name="specificProductIds.*" />
            </flux:field>

            @if ($productResults->isNotEmpty())
                <div class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    @foreach ($productResults as $product)
                        <button type="button" wire:click="addProduct({{ $product->id }})" wire:key="product-result-{{ $product->id }}"
                                class="block w-full px-3 py-2 text-left text-sm text-zinc-900 hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-800">
                            {{ $product->title }}
                        </button>
                    @endforeach
                </div>
            @endif

            @if ($selectedProducts->isNotEmpty())
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($selectedProducts as $product)
                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-sm text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100" wire:key="selected-product-{{ $product->id }}">
                            {{ $product->title }}
                            <button type="button" wire:click="removeProduct({{ $product->id }})" aria-label="Remove {{ $product->title }}" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                <flux:icon name="x-mark" class="size-3" />
                            </button>
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Specific collections picker --}}
            <flux:field class="mt-4">
                <flux:label for="collectionSearch">Specific collections</flux:label>
                <flux:input id="collectionSearch" icon="magnifying-glass" wire:model.live.debounce.300ms="collectionSearch" placeholder="Search collections..." />
                <flux:description>Leave empty to apply to all collections</flux:description>
                <flux:error name="specificCollectionIds.*" />
            </flux:field>

            @if ($collectionResults->isNotEmpty())
                <div class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    @foreach ($collectionResults as $collection)
                        <button type="button" wire:click="addCollection({{ $collection->id }})" wire:key="collection-result-{{ $collection->id }}"
                                class="block w-full px-3 py-2 text-left text-sm text-zinc-900 hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-zinc-800">
                            {{ $collection->title }}
                        </button>
                    @endforeach
                </div>
            @endif

            @if ($selectedCollections->isNotEmpty())
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($selectedCollections as $collection)
                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-3 py-1 text-sm text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100" wire:key="selected-collection-{{ $collection->id }}">
                            {{ $collection->title }}
                            <button type="button" wire:click="removeCollection({{ $collection->id }})" aria-label="Remove {{ $collection->title }}" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                <flux:icon name="x-mark" class="size-3" />
                            </button>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Usage limits section --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">Usage limits</flux:heading>

            <flux:field class="mt-4">
                <flux:label for="usageLimit">Total usage limit</flux:label>
                <flux:input id="usageLimit" type="number" min="1" wire:model.blur="usageLimit" placeholder="Unlimited" />
                <flux:error name="usageLimit" />
            </flux:field>

            @if ($this->isEditing())
                <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Used {{ $discount->usage_count }} {{ Str::plural('time', $discount->usage_count) }} so far.</flux:text>
            @endif
        </div>

        {{-- Active dates section --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">Active dates</flux:heading>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label for="startsAt">Start date</flux:label>
                    <flux:input id="startsAt" type="datetime-local" wire:model.blur="startsAt" />
                    <flux:error name="startsAt" />
                </flux:field>

                <flux:field>
                    <flux:label for="endsAt">End date</flux:label>
                    <flux:input id="endsAt" type="datetime-local" wire:model.blur="endsAt" />
                    <flux:description>Leave empty for no end date</flux:description>
                    <flux:error name="endsAt" />
                </flux:field>
            </div>
        </div>

        {{-- Status section --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">Status</flux:heading>
            @if ($this->isEditing() && $discount->status === \App\Enums\DiscountStatus::Expired)
                <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">This discount has expired and cannot be re-activated.</flux:text>
            @else
                <div class="mt-3">
                    <flux:switch wire:model.live="isActive" :label="$isActive ? 'Active' : 'Disabled'" />
                </div>
            @endif
        </div>
    </div>

    {{-- Sticky save bar (spec 03 §19.2) --}}
    <div class="fixed inset-x-0 bottom-0 z-20 border-t border-zinc-200 bg-white/95 backdrop-blur lg:left-64 dark:border-zinc-700 dark:bg-zinc-900/95">
        <div class="mx-auto flex max-w-7xl items-center justify-end gap-3 px-4 py-3 sm:px-6 lg:px-8">
            <flux:button variant="ghost" :href="route('admin.discounts.index')" wire:navigate>Discard</flux:button>
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </div>
</div>
