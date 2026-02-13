<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $mode === 'edit' ? 'Edit Discount' : 'Create Discount' }}</flux:heading>
        <a href="{{ route('admin.discounts.index') }}">
            <flux:button variant="ghost">Back to Discounts</flux:button>
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="space-y-4">
                <flux:input wire:model="code" label="Discount Code" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="type" label="Type">
                        <option value="code">Code</option>
                        <option value="automatic">Automatic</option>
                    </flux:select>
                    <flux:select wire:model="valueType" label="Value Type">
                        <option value="percent">Percentage</option>
                        <option value="fixed">Fixed Amount</option>
                        <option value="free_shipping">Free Shipping</option>
                    </flux:select>
                </div>

                <flux:input wire:model="valueAmount" label="Value Amount (in cents)" type="number" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="startsAt" label="Starts At" type="date" />
                    <flux:input wire:model="endsAt" label="Ends At" type="date" />
                </div>

                <flux:input wire:model="usageLimit" label="Usage Limit" type="number" />
                <flux:input wire:model="minPurchaseAmount" label="Min Purchase Amount (cents)" type="number" />

                <flux:select wire:model="status" label="Status">
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="disabled">Disabled</option>
                </flux:select>
            </div>
        </div>

        <flux:button type="submit" variant="primary">
            {{ $mode === 'edit' ? 'Update Discount' : 'Create Discount' }}
        </flux:button>
    </form>
</div>
