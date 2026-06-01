@php
    use App\Support\Storefront\PriceFormatter;
@endphp

<div>
    <div x-data="storefrontDialog($wire, 'open')"
         x-on:keydown.escape.window="$wire.open && $wire.set('open', false)"
         x-on:keydown.tab="trapTab($event)"
         @class(['fixed inset-0 z-50 flex justify-end', 'hidden' => ! $open])
         role="dialog" aria-modal="true" aria-label="{{ __('Shopping cart') }}">
        {{-- Backdrop. --}}
        <div class="absolute inset-0 bg-zinc-900/50" wire:click="$set('open', false)"></div>

        <aside class="relative flex h-full w-full max-w-md flex-col bg-white shadow-xl dark:bg-zinc-950" data-testid="cart-drawer">
            <header class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ __('Your cart') }}
                    @if ($lines->isNotEmpty())
                        <span class="text-zinc-400">({{ $lines->sum('quantity') }})</span>
                    @endif
                </h2>
                <button type="button" wire:click="$set('open', false)"
                        class="rounded-lg p-1 text-zinc-400 transition hover:text-zinc-900 dark:hover:text-white"
                        aria-label="{{ __('Close cart') }}">
                    <flux:icon.x-mark class="size-5" />
                </button>
            </header>

            <div class="flex-1 overflow-y-auto px-6 py-4" aria-live="polite">
                @forelse ($lines as $line)
                    <div class="flex gap-3 border-b border-zinc-100 py-4 dark:border-zinc-800" wire:key="cart-line-{{ $line->id }}" wire:loading.class="opacity-50" wire:target="increment({{ $line->id }}),decrement({{ $line->id }}),remove({{ $line->id }})">
                        <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            @php $img = $line->variant?->product?->primaryImage(); @endphp
                            @if ($img)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($img->storage_key) }}" alt="" class="h-full w-full object-cover" />
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $line->variant?->product?->title }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ PriceFormatter::format($line->unit_price_amount, $cart->currency) }}</p>
                            <div class="mt-2 flex items-center justify-between">
                                <div class="inline-flex items-stretch rounded-lg border border-zinc-300 dark:border-zinc-700">
                                    <button type="button" wire:click="decrement({{ $line->id }})"
                                            class="flex size-8 items-center justify-center text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white"
                                            aria-label="{{ __('Decrease quantity') }}">&minus;</button>
                                    <span class="flex w-8 items-center justify-center border-x border-zinc-300 text-sm dark:border-zinc-700" data-testid="line-qty-{{ $line->id }}">{{ $line->quantity }}</span>
                                    <button type="button" wire:click="increment({{ $line->id }})"
                                            class="flex size-8 items-center justify-center text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white"
                                            aria-label="{{ __('Increase quantity') }}">&plus;</button>
                                </div>
                                <button type="button" wire:click="remove({{ $line->id }})"
                                        class="text-zinc-400 transition hover:text-red-600"
                                        aria-label="{{ __('Remove :item from cart', ['item' => $line->variant?->product?->title]) }}">
                                    <flux:icon.trash class="size-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex h-full flex-col items-center justify-center text-center">
                        <flux:icon.shopping-bag class="size-12 text-zinc-300 dark:text-zinc-600" />
                        <p class="mt-4 text-zinc-500 dark:text-zinc-400">{{ __('Your cart is empty') }}</p>
                        <button type="button" wire:click="$set('open', false)"
                                class="mt-4 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                            {{ __('Continue shopping') }}
                        </button>
                    </div>
                @endforelse
            </div>

            @if ($lines->isNotEmpty())
                <footer class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-800">
                    <div class="mb-1 flex items-center justify-between text-sm font-medium text-zinc-900 dark:text-white">
                        <span>{{ __('Subtotal') }}</span>
                        <span data-testid="cart-subtotal">{{ PriceFormatter::format($subtotal, $cart->currency) }}</span>
                    </div>
                    <p class="mb-4 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Shipping and taxes calculated at checkout') }}</p>
                    <a href="{{ route('storefront.checkout') }}" wire:navigate
                       class="block w-full rounded-lg bg-blue-600 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-blue-700">
                        {{ __('Checkout') }}
                    </a>
                    <button type="button" wire:click="$set('open', false)"
                            class="mt-3 block w-full text-center text-sm text-zinc-500 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                        {{ __('Continue shopping') }}
                    </button>
                </footer>
            @endif
        </aside>
    </div>
</div>
