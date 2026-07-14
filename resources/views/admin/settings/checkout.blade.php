<div class="space-y-6">
    <x-admin.page-header title="Checkout Settings" description="Control how customers complete their purchases." />
    <nav class="flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-800"><a href="{{ url('/admin/settings') }}" wire:navigate class="px-4 py-2 text-sm text-zinc-500">General</a><a href="{{ url('/admin/settings?tab=domains') }}" wire:navigate class="px-4 py-2 text-sm text-zinc-500">Domains</a><a href="{{ url('/admin/settings/shipping') }}" wire:navigate class="px-4 py-2 text-sm text-zinc-500">Shipping</a><a href="{{ url('/admin/settings/taxes') }}" wire:navigate class="px-4 py-2 text-sm text-zinc-500">Taxes</a><span class="border-b-2 border-blue-600 px-4 py-2 text-sm font-medium text-blue-700">Checkout</span><a href="{{ url('/admin/settings/notifications') }}" wire:navigate class="px-4 py-2 text-sm text-zinc-500">Notifications</a></nav>
    <form wire:submit="save" class="space-y-6">
        <x-admin.form-section title="Customer information" description="Choose which details are required during checkout."><div class="space-y-4"><flux:checkbox wire:model="allowGuestCheckout" label="Allow guest checkout" /><flux:checkbox wire:model="requirePhone" label="Require a phone number" /><flux:checkbox wire:model="requireCompany" label="Require a company name" /></div></x-admin.form-section>
        <x-admin.form-section title="Checkout lifetime" description="Expire inactive checkouts and release their inventory reservations."><flux:input wire:model="checkoutExpiryHours" type="number" min="1" max="168" label="Expiry (hours)" /></x-admin.form-section>
        <div class="flex justify-end"><flux:button type="submit" variant="primary">Save</flux:button></div>
    </form>
</div>
