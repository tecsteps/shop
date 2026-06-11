<form wire:submit="save" class="space-y-6">
    <x-admin.card>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <flux:heading>{{ __('Checkout') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Control how customers complete their purchase.') }}</flux:text>
            </div>

            <div class="space-y-5 lg:col-span-2">
                <flux:field variant="inline">
                    <flux:switch wire:model="guestCheckoutEnabled" data-test="guest-checkout-switch" />
                    <flux:label>{{ __('Allow guest checkout') }}</flux:label>
                </flux:field>

                <flux:field class="max-w-48">
                    <flux:label>{{ __('Cancel unpaid bank transfers after') }}</flux:label>
                    <flux:input wire:model="bankTransferCancelDays" type="number" min="1" max="60" data-test="bank-transfer-cancel-days-input" />
                    <flux:description>{{ __('Days before unpaid bank transfer orders are cancelled automatically.') }}</flux:description>
                    <flux:error name="bankTransferCancelDays" />
                </flux:field>
            </div>
        </div>
    </x-admin.card>

    <div class="flex justify-end">
        <flux:button type="submit" variant="primary" data-test="save-checkout-settings-button">
            <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
            <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
        </flux:button>
    </div>
</form>
