<div>
    {{-- Minimal functional cart drawer. Storefront teammate (task #6) replaces
         the markup with the polished slide-out panel; the wire: bindings and
         the cart-updated event contract stay the same. --}}
    <div @class([
        'fixed inset-0 z-50 flex justify-end',
        'hidden' => ! $open,
    ])>
        <div class="absolute inset-0 bg-black/40" wire:click="$set('open', false)"></div>

        <aside class="relative flex h-full w-full max-w-md flex-col bg-white shadow-xl" data-testid="cart-drawer">
            <header class="flex items-center justify-between border-b border-zinc-200 px-6 py-4">
                <h2 class="text-lg font-semibold">{{ __('Your cart') }}</h2>
                <button type="button" wire:click="$set('open', false)" class="text-zinc-400 hover:text-zinc-600">&times;</button>
            </header>

            <div class="flex-1 overflow-y-auto px-6 py-4">
                @forelse ($lines as $line)
                    <div class="flex items-center justify-between gap-4 border-b border-zinc-100 py-3" wire:key="cart-line-{{ $line->id }}">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-zinc-900">{{ $line->variant?->product?->title }}</p>
                            <p class="text-xs text-zinc-500">{{ \App\Support\Storefront\PriceFormatter::format($line->unit_price_amount, $cart->currency) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="decrement({{ $line->id }})" class="h-6 w-6 rounded border border-zinc-300">-</button>
                            <span class="w-6 text-center text-sm" data-testid="line-qty-{{ $line->id }}">{{ $line->quantity }}</span>
                            <button type="button" wire:click="increment({{ $line->id }})" class="h-6 w-6 rounded border border-zinc-300">+</button>
                            <button type="button" wire:click="remove({{ $line->id }})" class="ml-2 text-xs text-red-600">{{ __('Remove') }}</button>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-zinc-500">{{ __('Your cart is empty.') }}</p>
                @endforelse
            </div>

            <footer class="border-t border-zinc-200 px-6 py-4">
                <div class="mb-4 flex items-center justify-between text-sm font-medium">
                    <span>{{ __('Subtotal') }}</span>
                    <span data-testid="cart-subtotal">{{ \App\Support\Storefront\PriceFormatter::format($subtotal, $cart->currency) }}</span>
                </div>
                {{-- The checkout route is registered by the storefront teammate
                     (task #6); fall back to a plain path until then. --}}
                <a href="{{ \Illuminate\Support\Facades\Route::has('checkout.show') ? route('checkout.show') : '/checkout' }}" wire:navigate
                   @class(['pointer-events-none opacity-50' => $lines->isEmpty()])
                   class="block w-full rounded-lg bg-zinc-900 px-4 py-3 text-center text-sm font-semibold text-white">
                    {{ __('Checkout') }}
                </a>
            </footer>
        </aside>
    </div>
</div>
