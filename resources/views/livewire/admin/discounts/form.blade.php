<div>
    <x-slot:title>{{ $discount ? 'Edit Discount' : 'Create Discount' }}</x-slot:title>

    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ $discount ? 'Edit Discount' : 'Create Discount' }}</flux:heading>
        <flux:button href="{{ route('admin.discounts.index') }}" variant="ghost" wire:navigate>Back to Discounts</flux:button>
    </div>

    <form wire:submit="save" class="mt-6 space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Code</flux:label>
                        <flux:input wire:model="code" />
                        <flux:error name="code" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Status</flux:label>
                        <flux:select wire:model="status">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="disabled">Disabled</option>
                        </flux:select>
                    </flux:field>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:field>
                        <flux:label>Discount Type</flux:label>
                        <flux:select wire:model="type">
                            <option value="code">Code</option>
                            <option value="automatic">Automatic</option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Value Type</flux:label>
                        <flux:select wire:model="valueType">
                            <option value="percent">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
                            <option value="free_shipping">Free Shipping</option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Value</flux:label>
                        <flux:input wire:model="value" type="number" min="0" />
                        <flux:error name="value" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Start Date</flux:label>
                        <flux:input wire:model="startsAt" type="date" />
                    </flux:field>

                    <flux:field>
                        <flux:label>End Date</flux:label>
                        <flux:input wire:model="endsAt" type="date" />
                        <flux:error name="endsAt" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Usage Limit</flux:label>
                        <flux:input wire:model="usageLimit" type="number" min="0" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Minimum Purchase Amount (cents)</flux:label>
                        <flux:input wire:model="minimumPurchaseAmount" type="number" min="0" />
                    </flux:field>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <flux:button href="{{ route('admin.discounts.index') }}" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">
                {{ $discount ? 'Update Discount' : 'Create Discount' }}
            </flux:button>
        </div>
    </form>
</div>
