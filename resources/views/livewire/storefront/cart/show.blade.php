<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    {{-- Minimal functional cart page. Storefront teammate (task #6) restyles. --}}
    <h1 class="mb-8 text-2xl font-bold tracking-tight">{{ __('Your cart') }}</h1>

    @forelse ($lines as $line)
        <div class="flex items-center justify-between gap-4 border-b border-zinc-100 py-4" wire:key="cart-line-{{ $line->id }}">
            <div class="min-w-0">
                <p class="truncate font-medium text-zinc-900">{{ $line->variant?->product?->title }}</p>
                <p class="text-sm text-zinc-500">{{ \App\Support\Storefront\PriceFormatter::format($line->unit_price_amount, $cart->currency) }}</p>
            </div>
            <div class="flex items-center gap-3">
                <input type="number" min="0" value="{{ $line->quantity }}"
                       wire:change="updateQuantity({{ $line->id }}, $event.target.value)"
                       class="w-16 rounded border border-zinc-300 px-2 py-1 text-sm" data-testid="line-qty-{{ $line->id }}">
                <span class="w-20 text-right text-sm font-medium" data-testid="line-total-{{ $line->id }}">
                    {{ \App\Support\Storefront\PriceFormatter::format($line->line_total_amount, $cart->currency) }}
                </span>
                <button type="button" wire:click="remove({{ $line->id }})" class="text-sm text-red-600">{{ __('Remove') }}</button>
            </div>
        </div>
    @empty
        <p class="py-12 text-center text-zinc-500">{{ __('Your cart is empty.') }}</p>
    @endforelse

    @if ($lines->isNotEmpty())
        <div class="mt-8 flex items-center justify-between border-t border-zinc-200 pt-6">
            <span class="text-lg font-semibold">{{ __('Subtotal') }}</span>
            <span class="text-lg font-semibold" data-testid="cart-subtotal">{{ \App\Support\Storefront\PriceFormatter::format($subtotal, $cart->currency) }}</span>
        </div>
        <a href="{{ \Illuminate\Support\Facades\Route::has('checkout.show') ? route('checkout.show') : '/checkout' }}" wire:navigate
           class="mt-6 block w-full rounded-lg bg-zinc-900 px-4 py-3 text-center font-semibold text-white">
            {{ __('Proceed to checkout') }}
        </a>
    @endif
</div>
