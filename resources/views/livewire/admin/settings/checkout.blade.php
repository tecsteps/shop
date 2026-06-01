<div>
    <form wire:submit="save" class="max-w-lg space-y-5">
        <flux:heading size="lg">{{ __('Checkout') }}</flux:heading>

        <flux:switch wire:model="guestCheckoutEnabled" :label="__('Allow guest checkout')" />
        <flux:switch wire:model="requirePhone" :label="__('Require phone number')" />

        <flux:field>
            <flux:label>{{ __('Terms & conditions URL') }}</flux:label>
            <flux:input type="url" wire:model="termsUrl" placeholder="https://example.com/terms" />
            <flux:error name="termsUrl" />
        </flux:field>

        <div>
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>
