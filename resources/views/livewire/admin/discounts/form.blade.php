<div>
    <flux:heading size="xl">{{ $discount ? __('Edit Discount') : __('New Discount') }}</flux:heading>

    <form wire:submit="save" class="mt-6 space-y-6 max-w-2xl">
        <flux:field>
            <flux:select wire:model="type" :label="__('Type')">
                <flux:select.option value="code">{{ __('Discount code') }}</flux:select.option>
                <flux:select.option value="automatic">{{ __('Automatic') }}</flux:select.option>
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:input wire:model="code" :label="__('Code')" required />
        </flux:field>

        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:select wire:model="value_type" :label="__('Value type')">
                    <flux:select.option value="percent">{{ __('Percentage') }}</flux:select.option>
                    <flux:select.option value="fixed">{{ __('Fixed amount') }}</flux:select.option>
                    <flux:select.option value="free_shipping">{{ __('Free shipping') }}</flux:select.option>
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:input wire:model="value_amount" :label="__('Value')" type="number" min="0" required />
            </flux:field>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:input wire:model="starts_at" :label="__('Starts at')" type="datetime-local" />
            </flux:field>
            <flux:field>
                <flux:input wire:model="ends_at" :label="__('Ends at')" type="datetime-local" />
            </flux:field>
        </div>

        <flux:field>
            <flux:input wire:model="usage_limit" :label="__('Usage limit')" type="number" min="1" />
        </flux:field>

        <flux:field>
            <flux:select wire:model="status" :label="__('Status')">
                <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="disabled">{{ __('Disabled') }}</flux:select.option>
            </flux:select>
        </flux:field>

        <flux:separator />

        <div class="flex items-center justify-end gap-2">
            <flux:button :href="route('admin.discounts.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button variant="primary" type="submit">{{ $discount ? __('Save') : __('Create discount') }}</flux:button>
        </div>
    </form>
</div>
