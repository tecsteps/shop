<div class="space-y-6">
    <x-admin.page-header title="Notification Settings" description="Choose the transactional emails sent by your store." />
    <nav class="flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-800"><a href="{{ url('/admin/settings') }}" wire:navigate class="px-4 py-2 text-sm text-zinc-500">General</a><a href="{{ url('/admin/settings?tab=domains') }}" wire:navigate class="px-4 py-2 text-sm text-zinc-500">Domains</a><a href="{{ url('/admin/settings/shipping') }}" wire:navigate class="px-4 py-2 text-sm text-zinc-500">Shipping</a><a href="{{ url('/admin/settings/taxes') }}" wire:navigate class="px-4 py-2 text-sm text-zinc-500">Taxes</a><a href="{{ url('/admin/settings/checkout') }}" wire:navigate class="px-4 py-2 text-sm text-zinc-500">Checkout</a><span class="border-b-2 border-blue-600 px-4 py-2 text-sm font-medium text-blue-700">Notifications</span></nav>
    <form wire:submit="save" class="space-y-6">
        <x-admin.form-section title="Customer emails" description="Send timely updates throughout the order lifecycle."><div class="space-y-4"><flux:checkbox wire:model="orderConfirmation" label="Order confirmation" /><flux:checkbox wire:model="shippingConfirmation" label="Shipping confirmation" /><flux:checkbox wire:model="refundConfirmation" label="Refund confirmation" /><flux:checkbox wire:model="cancellationConfirmation" label="Cancellation confirmation" /></div></x-admin.form-section>
        <x-admin.form-section title="Staff emails" description="Keep the store team informed."><flux:checkbox wire:model="notifyStaffOfNewOrders" label="Notify staff when a new order is placed" /></x-admin.form-section>
        <div class="flex justify-end"><flux:button type="submit" variant="primary">Save</flux:button></div>
    </form>
</div>
