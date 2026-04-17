<div class="flex flex-col gap-8">
    <h1 class="text-3xl font-semibold tracking-tight">Your cart</h1>

    @if ($cart === null || $lines->isEmpty())
        <div class="rounded-lg border border-dashed border-neutral-300 p-8 text-center text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">
            <p>Your cart is empty.</p>
            <a href="{{ url('/collections/all') }}" class="mt-4 inline-block rounded-full bg-neutral-900 px-6 py-2 text-sm font-semibold text-white hover:bg-neutral-700">Continue shopping</a>
        </div>
    @else
        <div class="flex flex-col gap-4">
            @foreach ($lines as $line)
                <div wire:key="line-{{ $line->id }}" class="flex items-center justify-between gap-4 rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                    <div class="flex-1">
                        <div class="font-medium">{{ $line->variant?->product?->title ?? 'Variant' }}</div>
                        <div class="text-xs text-neutral-500">SKU: {{ $line->variant?->sku ?? 'N/A' }}</div>
                        @error('line_'.$line->id)
                            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="flex items-center gap-3">
                        <input
                            type="number"
                            min="1"
                            value="{{ $line->quantity }}"
                            wire:change="updateQuantity({{ $line->id }}, $event.target.value)"
                            class="w-16 rounded border border-neutral-300 px-2 py-1 text-sm dark:border-neutral-700 dark:bg-neutral-900"
                        />
                        <div class="w-24 text-right text-sm font-semibold">
                            {{ number_format($line->line_total_amount / 100, 2) }} {{ $cart->currency }}
                        </div>
                        <button type="button" wire:click="removeLine({{ $line->id }})" class="text-xs text-neutral-500 hover:text-red-600">
                            Remove
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between border-t border-neutral-200 pt-4 dark:border-neutral-800">
            <div class="text-lg font-semibold">
                Subtotal: {{ number_format($cart->subtotal() / 100, 2) }} {{ $cart->currency }}
            </div>
            <a href="{{ url('/checkout') }}" class="rounded-full bg-neutral-900 px-6 py-3 text-sm font-semibold text-white hover:bg-neutral-700">
                Checkout
            </a>
        </div>
    @endif
</div>
