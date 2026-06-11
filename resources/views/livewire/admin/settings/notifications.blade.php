<form wire:submit="save" class="space-y-6">
    <x-admin.card>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <flux:heading>{{ __('Notifications') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Transactional emails and internal order alerts.') }}</flux:text>
            </div>

            <div class="space-y-5 lg:col-span-2">
                <flux:field>
                    <flux:label>{{ __('Notification email') }}</flux:label>
                    <flux:input wire:model="notificationEmail" type="email" placeholder="orders@example.com" data-test="notification-email-input" />
                    <flux:description>{{ __('Internal alerts about new orders are sent to this address.') }}</flux:description>
                    <flux:error name="notificationEmail" />
                </flux:field>

                <flux:field variant="inline">
                    <flux:switch wire:model="sendOrderConfirmation" data-test="order-confirmation-switch" />
                    <flux:label>{{ __('Send order confirmation emails to customers') }}</flux:label>
                </flux:field>

                <flux:field variant="inline">
                    <flux:switch wire:model="sendShippingConfirmation" data-test="shipping-confirmation-switch" />
                    <flux:label>{{ __('Send shipping confirmation emails to customers') }}</flux:label>
                </flux:field>

                <flux:field variant="inline">
                    <flux:switch wire:model="notifyOnNewOrder" data-test="new-order-alert-switch" />
                    <flux:label>{{ __('Notify me when a new order is placed') }}</flux:label>
                </flux:field>
            </div>
        </div>
    </x-admin.card>

    <div class="flex justify-end">
        <flux:button type="submit" variant="primary" data-test="save-notification-settings-button">
            <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
            <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
        </flux:button>
    </div>
</form>
