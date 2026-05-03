<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-semibold tracking-normal">Cart</h1>
    @if(! $cart || $cart->lines->isEmpty())
        <div class="mt-8 rounded-lg border border-zinc-200 p-6 dark:border-zinc-800">
            <p class="text-zinc-600 dark:text-zinc-400">Your cart is empty.</p>
            <a href="/collections" class="mt-5 inline-flex rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
                Continue shopping
            </a>
        </div>
    @else
        <div class="mt-8 flex flex-col gap-6">
            @error('cart')
                <p class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">{{ $message }}</p>
            @enderror

            @foreach($cart->lines as $line)
                <div wire:key="cart-line-{{ $line->id }}" class="grid gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-800 sm:grid-cols-[1fr_auto]">
                    <div>
                        <div class="font-semibold">{{ $line->variant->product->title }}</div>
                        <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $line->variant->optionValues->pluck('value')->join(' / ') ?: 'Default' }}</div>
                        <div class="mt-3 text-sm font-semibold">
                            @include('storefront.components.price', ['amount' => $line->line_total_amount, 'currency' => $cart->currency])
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" min="0" max="9999" value="{{ $line->quantity }}" wire:change="updateQuantity({{ $line->id }}, $event.target.value)" class="w-20 rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <button type="button" wire:click="removeLine({{ $line->id }})" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">
                            Remove
                        </button>
                    </div>
                </div>
            @endforeach

            <div class="rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
                <div class="flex justify-between text-sm">
                    <span>Subtotal</span>
                    <span>@include('storefront.components.price', ['amount' => $cart->totalAmount(), 'currency' => $cart->currency])</span>
                </div>
                <form wire:submit="startCheckout" class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <input wire:model="email" type="email" placeholder="Email" class="min-w-0 flex-1 rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <button type="submit" class="rounded-md bg-zinc-950 px-5 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
                        Checkout
                    </button>
                </form>
                @error('email')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    @endif
</div>
