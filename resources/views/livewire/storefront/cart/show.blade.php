@php
    use App\Support\Storefront\PriceFormatter;
@endphp

<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront::breadcrumbs :items="[
        ['label' => __('Home'), 'url' => route('storefront.home')],
        ['label' => __('Cart')],
    ]" />

    <h1 class="mb-8 mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">{{ __('Your cart') }}</h1>

    @if ($lines->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 py-16 text-center dark:border-zinc-700">
            <flux:icon.shopping-bag class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" />
            <p class="mt-4 text-zinc-500 dark:text-zinc-400">{{ __('Your cart is empty') }}</p>
            <a href="{{ route('storefront.home') }}" wire:navigate
               class="mt-4 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ __('Continue shopping') }}</a>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
            @foreach ($lines as $line)
                <div class="flex items-center gap-4 border-b border-zinc-100 p-4 last:border-b-0 dark:border-zinc-800" wire:key="cart-line-{{ $line->id }}" wire:loading.class="opacity-50" wire:target="remove({{ $line->id }})">
                    <div class="size-20 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        @php $img = $line->variant?->product?->primaryImage(); @endphp
                        @if ($img)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($img->storage_key) }}" alt="" class="h-full w-full object-cover" />
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $line->variant?->product?->title }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ PriceFormatter::format($line->unit_price_amount, $cart->currency) }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <input type="number" min="1" value="{{ $line->quantity }}"
                               wire:change="updateQuantity({{ $line->id }}, $event.target.value)"
                               class="w-16 rounded-lg border border-zinc-300 px-2 py-1.5 text-center text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                               aria-label="{{ __('Quantity') }}" data-testid="line-qty-{{ $line->id }}">
                        <span class="w-24 text-right text-sm font-medium text-zinc-900 dark:text-white" data-testid="line-total-{{ $line->id }}">
                            {{ PriceFormatter::format($line->line_total_amount, $cart->currency) }}
                        </span>
                        <button type="button" wire:click="remove({{ $line->id }})"
                                class="text-zinc-400 transition hover:text-red-600"
                                aria-label="{{ __('Remove :item from cart', ['item' => $line->variant?->product?->title]) }}">
                            <flux:icon.trash class="size-4" />
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex flex-col items-end gap-4">
            <div class="flex w-full max-w-xs items-center justify-between text-lg font-semibold text-zinc-900 dark:text-white">
                <span>{{ __('Subtotal') }}</span>
                <span data-testid="cart-subtotal">{{ PriceFormatter::format($subtotal, $cart->currency) }}</span>
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Shipping and taxes calculated at checkout') }}</p>
            <a href="{{ route('storefront.checkout') }}" wire:navigate
               class="w-full max-w-xs rounded-lg bg-blue-600 px-4 py-3 text-center font-semibold text-white transition hover:bg-blue-700">
                {{ __('Proceed to checkout') }}
            </a>
            <a href="{{ route('storefront.home') }}" wire:navigate
               class="text-sm text-zinc-500 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ __('Continue shopping') }}</a>
        </div>
    @endif
</div>
