<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $discountId ? 'Edit discount' : 'Create discount' }}</flux:heading>
        <flux:button variant="ghost" href="{{ url('/admin/discounts') }}">Back</flux:button>
    </div>

    <form wire:submit="save" class="space-y-4">
        <div class="space-y-3 rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
            <flux:field>
                <flux:label>Type</flux:label>
                <flux:select wire:model.live="type">
                    <flux:select.option value="code">Discount code</flux:select.option>
                    <flux:select.option value="automatic">Automatic</flux:select.option>
                </flux:select>
            </flux:field>
            @if ($type === 'code')
                <flux:field>
                    <flux:label>Code</flux:label>
                    <flux:input wire:model="code" placeholder="SUMMER20" />
                    <flux:error name="code" />
                </flux:field>
            @endif
        </div>

        <div class="space-y-3 rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
            <flux:field>
                <flux:label>Value type</flux:label>
                <flux:select wire:model.live="valueType">
                    <flux:select.option value="percent">Percentage</flux:select.option>
                    <flux:select.option value="fixed">Fixed amount</flux:select.option>
                    <flux:select.option value="free_shipping">Free shipping</flux:select.option>
                </flux:select>
            </flux:field>
            @if ($valueType !== 'free_shipping')
                <flux:field>
                    <flux:label>Amount</flux:label>
                    <flux:input type="number" wire:model="valueAmount" min="0" />
                    <flux:description>Percent in whole numbers, or cents for fixed amount.</flux:description>
                </flux:field>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-3 rounded-lg border border-neutral-200 bg-white p-4 sm:grid-cols-2 dark:border-neutral-800 dark:bg-neutral-900">
            <flux:field>
                <flux:label>Starts at</flux:label>
                <flux:input type="datetime-local" wire:model="startsAt" />
                <flux:error name="startsAt" />
            </flux:field>
            <flux:field>
                <flux:label>Ends at</flux:label>
                <flux:input type="datetime-local" wire:model="endsAt" />
            </flux:field>
        </div>

        <div class="grid grid-cols-1 gap-3 rounded-lg border border-neutral-200 bg-white p-4 sm:grid-cols-2 dark:border-neutral-800 dark:bg-neutral-900">
            <flux:field>
                <flux:label>Usage limit</flux:label>
                <flux:input type="number" wire:model="usageLimit" placeholder="Unlimited" min="1" />
            </flux:field>
            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model="status">
                    <flux:select.option value="draft">Draft</flux:select.option>
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="expired">Expired</flux:select.option>
                    <flux:select.option value="disabled">Disabled</flux:select.option>
                </flux:select>
            </flux:field>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save discount</flux:button>
        </div>
    </form>
</div>
