<div>
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('admin.dashboard') }}" wire:navigate>Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('admin.discounts.index') }}" wire:navigate>Discounts</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $discount ? ($discount->code ?? 'Edit discount') : 'Create discount' }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <flux:heading size="xl" class="mb-6">{{ $discount ? 'Edit discount' : 'Create discount' }}</flux:heading>

    <div class="max-w-2xl space-y-6">
        {{-- Type --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:radio.group wire:model.live="type" label="Discount type">
                <flux:radio value="code" label="Discount code" description="Customers enter a code at checkout" />
                <flux:radio value="automatic" label="Automatic discount" description="Applied automatically at checkout" />
            </flux:radio.group>
        </div>

        {{-- Code --}}
        @if ($type === 'code')
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <flux:field>
                            <flux:label>Discount code</flux:label>
                            <flux:input wire:model="code" placeholder="SUMMER20" />
                        </flux:field>
                    </div>
                    <flux:button variant="ghost" wire:click="generateCode">Generate</flux:button>
                </div>
            </div>
        @endif

        {{-- Value --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:radio.group wire:model.live="valueType" label="Value type">
                <flux:radio value="percent" label="Percentage" />
                <flux:radio value="fixed" label="Fixed amount" />
                <flux:radio value="free_shipping" label="Free shipping" />
            </flux:radio.group>

            @if ($valueType !== 'free_shipping')
                <div class="mt-4">
                    <flux:field>
                        <flux:label>{{ $valueType === 'percent' ? 'Percentage' : 'Amount' }}</flux:label>
                        <flux:input wire:model="valueAmount" type="number" step="{{ $valueType === 'percent' ? '1' : '0.01' }}" />
                    </flux:field>
                </div>
            @endif
        </div>

        {{-- Conditions --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:heading size="md" class="mb-4">Conditions</flux:heading>
            <flux:field>
                <flux:label>Minimum purchase amount</flux:label>
                <flux:input wire:model="minimumPurchaseAmount" type="number" step="0.01" placeholder="0.00" />
                <flux:description>Leave empty for no minimum</flux:description>
            </flux:field>
        </div>

        {{-- Usage Limits --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:heading size="md" class="mb-4">Usage limits</flux:heading>
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Total usage limit</flux:label>
                    <flux:input wire:model="usageLimit" type="number" placeholder="Unlimited" />
                </flux:field>
                <flux:checkbox wire:model="onePerCustomer" label="Limit to one use per customer" />
            </div>
        </div>

        {{-- Active Dates --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:heading size="md" class="mb-4">Active dates</flux:heading>
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Start date</flux:label>
                    <flux:input type="datetime-local" wire:model="startsAt" />
                </flux:field>
                <flux:field>
                    <flux:label>End date</flux:label>
                    <flux:input type="datetime-local" wire:model="endsAt" />
                    <flux:description>Leave empty for no end date</flux:description>
                </flux:field>
            </div>
        </div>

        {{-- Status --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <flux:switch wire:model="isActive" label="Active" />
        </div>
    </div>

    <div class="fixed bottom-0 left-0 right-0 z-30 border-t border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900 lg:left-64">
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('admin.discounts.index') }}" wire:navigate>Discard</flux:button>
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
        </div>
    </div>
</div>
