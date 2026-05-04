<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Notifications</flux:heading>
            <flux:text class="mt-1">Sender identity, customer emails, and admin alerts.</flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('admin.settings.index')" wire:navigate variant="filled">General</flux:button>
            <flux:button :href="route('admin.settings.shipping')" wire:navigate variant="filled">Shipping</flux:button>
            <flux:button :href="route('admin.settings.taxes')" wire:navigate variant="filled">Taxes</flux:button>
            <flux:button :href="route('admin.settings.checkout')" wire:navigate variant="filled">Checkout</flux:button>
            <flux:button :href="route('admin.settings.notifications')" wire:navigate variant="primary">Notifications</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-6 p-5 lg:grid-cols-[260px_1fr]">
                <div>
                    <flux:heading size="lg">Sender</flux:heading>
                    <flux:text class="mt-1">Outbound email identity.</flux:text>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <flux:input wire:model="senderName" label="Sender name" />
                    <flux:input wire:model="senderEmail" type="email" label="Sender email" />
                    <flux:input wire:model="replyToEmail" type="email" label="Reply-to email" />
                </div>
            </div>

            <flux:separator />

            <div class="grid gap-6 p-5 lg:grid-cols-[260px_1fr]">
                <div>
                    <flux:heading size="lg">Customer emails</flux:heading>
                    <flux:text class="mt-1">Transactional storefront messages.</flux:text>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <flux:switch wire:model="orderConfirmationEnabled" label="Order confirmation" align="left" />
                    <flux:switch wire:model="shippingConfirmationEnabled" label="Shipping confirmation" align="left" />
                    <flux:switch wire:model="refundConfirmationEnabled" label="Refund confirmation" align="left" />
                </div>
            </div>

            <flux:separator />

            <div class="grid gap-6 p-5 lg:grid-cols-[260px_1fr]">
                <div>
                    <flux:heading size="lg">Admin alerts</flux:heading>
                    <flux:text class="mt-1">Operational alert preferences.</flux:text>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <flux:switch wire:model="adminOrderAlertsEnabled" label="New order alerts" align="left" />
                    <flux:switch wire:model="lowStockAlertsEnabled" label="Low stock alerts" align="left" />
                    <flux:input wire:model="lowStockThreshold" type="number" min="0" max="999" label="Low stock threshold" />
                </div>
            </div>

            <div class="flex justify-end border-t border-zinc-200 p-5 dark:border-zinc-700">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save notifications</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
            </div>
        </div>
    </form>
</section>
