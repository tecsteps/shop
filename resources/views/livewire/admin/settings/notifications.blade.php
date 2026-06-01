<div>
    <form wire:submit="save" class="max-w-lg space-y-5">
        <flux:heading size="lg">{{ __('Notifications') }}</flux:heading>

        <flux:field>
            <flux:label>{{ __('Sender email') }}</flux:label>
            <flux:input type="email" wire:model="senderEmail" placeholder="orders@example.com" />
            <flux:error name="senderEmail" />
        </flux:field>

        <flux:switch wire:model="orderConfirmation" :label="__('Send order confirmation emails')" />
        <flux:switch wire:model="shippingConfirmation" :label="__('Send shipping confirmation emails')" />
        <flux:switch wire:model="refundNotification" :label="__('Send refund notification emails')" />

        <div>
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>
