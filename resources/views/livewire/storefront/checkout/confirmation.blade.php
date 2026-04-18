<div class="mx-auto max-w-3xl px-6 py-16 text-center" data-testid="checkout-confirmation">
    <flux:icon.check-circle class="mx-auto mb-4 size-16 text-emerald-500" />
    <flux:heading size="xl">Thank you for your order</flux:heading>
    <flux:text class="mt-2">Order number: <span class="font-mono font-semibold" data-testid="order-number">#{{ $number }}</span></flux:text>
    <flux:text variant="subtle" class="mt-3">Phase 5 will wire the real order and payment data.</flux:text>
    <flux:button class="mt-6" variant="primary" :href="route('storefront.home')">Continue shopping</flux:button>
</div>
