<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ $this->isEditing ? 'Edit discount' : 'Create discount' }}</flux:heading>
    </div>

    <form wire:submit="save" class="mx-auto max-w-2xl space-y-6">
        {{-- Type --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="md" class="mb-4">Type</flux:heading>
            <div class="space-y-3">
                <flux:radio wire:model.live="type" value="code" label="Discount code" description="Customers enter a code at checkout" />
                <flux:radio wire:model.live="type" value="automatic" label="Automatic discount" description="Applied automatically at checkout" />
            </div>
        </div>

        {{-- Code --}}
        @if ($type === 'code')
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-end gap-4">
                    <div class="flex-1">
                        <flux:field>
                            <flux:label>Discount code</flux:label>
                            <flux:input wire:model="code" placeholder="SUMMER20" />
                        </flux:field>
                    </div>
                    <flux:button variant="ghost" wire:click="generateCode" type="button">Generate</flux:button>
                </div>
            </div>
        @endif

        {{-- Value --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="md" class="mb-4">Value</flux:heading>
            <div class="mb-4 space-y-3">
                <flux:radio wire:model.live="valueType" value="percent" label="Percentage" />
                <flux:radio wire:model.live="valueType" value="fixed" label="Fixed amount" />
                <flux:radio wire:model.live="valueType" value="free_shipping" label="Free shipping" />
            </div>
            @if ($valueType !== 'free_shipping')
                <flux:field>
                    <flux:label>{{ $valueType === 'percent' ? 'Percentage' : 'Amount' }}</flux:label>
                    <flux:input wire:model="valueAmount" type="number" step="{{ $valueType === 'percent' ? '1' : '0.01' }}" placeholder="{{ $valueType === 'percent' ? '10' : '5.00' }}" />
                </flux:field>
            @endif
        </div>

        {{-- Conditions --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="md" class="mb-4">Conditions</flux:heading>
            <flux:field>
                <flux:label>Minimum purchase amount</flux:label>
                <flux:input wire:model="minimumPurchaseAmount" type="number" step="0.01" placeholder="0.00" />
                <flux:description>Leave empty for no minimum</flux:description>
            </flux:field>
        </div>

        {{-- Usage limits --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="md" class="mb-4">Usage limits</flux:heading>
            <div class="space-y-4">
                <flux:field>
                    <flux:label>Total usage limit</flux:label>
                    <flux:input wire:model="usageLimit" type="number" placeholder="Unlimited" />
                </flux:field>
                <flux:checkbox wire:model="onePerCustomer" label="Limit to one use per customer" />
            </div>
        </div>

        {{-- Active dates --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
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
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:switch wire:model="isActive" label="Active" />
        </div>

        {{-- Sticky save bar --}}
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white px-6 py-3 dark:border-zinc-700 dark:bg-zinc-800 lg:left-64">
            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" href="{{ route('admin.discounts.index') }}" wire:navigate>Discard</flux:button>
                <flux:button variant="primary" type="submit">Save</flux:button>
            </div>
        </div>
    </form>
</div>
