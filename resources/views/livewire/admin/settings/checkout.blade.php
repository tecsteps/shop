<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Checkout</flux:heading>
            <flux:text class="mt-1">Customer account, payment hold, and checkout policy settings.</flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('admin.settings.index')" wire:navigate variant="filled">General</flux:button>
            <flux:button :href="route('admin.settings.shipping')" wire:navigate variant="filled">Shipping</flux:button>
            <flux:button :href="route('admin.settings.taxes')" wire:navigate variant="filled">Taxes</flux:button>
            <flux:button :href="route('admin.settings.checkout')" wire:navigate variant="primary">Checkout</flux:button>
            <flux:button :href="route('admin.settings.notifications')" wire:navigate variant="filled">Notifications</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-6 p-5 lg:grid-cols-[260px_1fr]">
                <div>
                    <flux:heading size="lg">Accounts</flux:heading>
                    <flux:text class="mt-1">Sign-in and contact requirements.</flux:text>
                </div>

                <div class="space-y-4">
                    <flux:switch wire:model="guestCheckoutEnabled" label="Guest checkout enabled" align="left" />
                    <flux:switch wire:model="customerAccountsRequired" label="Customer account required" align="left" />
                    <flux:switch wire:model="phoneNumberRequired" label="Phone number required" align="left" />
                    <flux:switch wire:model="billingAddressEnabled" label="Separate billing address enabled" align="left" />
                </div>
            </div>

            <flux:separator />

            <div class="grid gap-6 p-5 lg:grid-cols-[260px_1fr]">
                <div>
                    <flux:heading size="lg">Checkout form</flux:heading>
                    <flux:text class="mt-1">Customer-facing fields and terms.</flux:text>
                </div>

                <div class="space-y-4">
                    <flux:switch wire:model="orderNotesEnabled" label="Order notes enabled" align="left" />
                    <flux:switch wire:model="termsRequired" label="Terms acceptance required" align="left" />
                    <flux:input wire:model="termsUrl" label="Terms URL" placeholder="https://shop.example.test/pages/terms" />
                    <flux:error name="termsUrl" />
                </div>
            </div>

            <flux:separator />

            <div class="grid gap-6 p-5 lg:grid-cols-[260px_1fr]">
                <div>
                    <flux:heading size="lg">Timing</flux:heading>
                    <flux:text class="mt-1">Reservation and cleanup windows.</flux:text>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <flux:input wire:model="paymentHoldHours" type="number" min="1" max="168" label="Payment hold hours" />
                    <flux:input wire:model="abandonedCheckoutDays" type="number" min="1" max="90" label="Abandoned checkout days" />
                    <flux:input wire:model="bankTransferCancelDays" type="number" min="1" max="60" label="Bank transfer cancel days" />
                </div>
            </div>

            <div class="flex justify-end border-t border-zinc-200 p-5 dark:border-zinc-700">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save checkout</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
            </div>
        </div>
    </form>
</section>
