<div class="mx-auto max-w-6xl px-4 py-12 sm:py-16 lg:px-8">
    <div class="flex flex-col gap-4 border-b border-zinc-200 pb-8 dark:border-zinc-800 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Shopping bag</p>
            <h1 class="mt-2 text-4xl font-bold tracking-tight text-zinc-950 dark:text-white">Your cart</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $cart->itemCount() }} {{ $cart->itemCount() === 1 ? 'item' : 'items' }}</p>
        </div>
        <a href="{{ route('collections.index') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-blue-600 underline decoration-blue-300 underline-offset-4 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 dark:focus:ring-offset-zinc-950" wire:navigate>
            Continue shopping
        </a>
    </div>

    @if ($message)
        <p class="mt-5 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:bg-green-950/40 dark:text-green-200" role="status" aria-live="polite">{{ $message }}</p>
    @endif

    @error('cart')
        <p class="mt-5 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:bg-red-950/40 dark:text-red-200" role="alert">{{ $message }}</p>
    @enderror

    @if ($cart->lines->isEmpty())
        <div class="py-24 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 text-2xl text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400" aria-hidden="true">🛍</div>
            <h2 class="mt-6 text-2xl font-semibold text-zinc-950 dark:text-white">Your cart is empty</h2>
            <p class="mx-auto mt-2 max-w-md text-zinc-600 dark:text-zinc-400">Add something you love to get started.</p>
            <a href="{{ route('collections.index') }}" class="mt-7 inline-flex min-h-11 items-center justify-center rounded-full bg-blue-600 px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 dark:focus:ring-offset-zinc-950" wire:navigate>
                Browse collections
            </a>
        </div>
    @else
        <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start">
            <section aria-labelledby="cart-items-heading">
                <h2 id="cart-items-heading" class="sr-only">Items in your cart</h2>

                <div class="space-y-4 lg:hidden">
                    @foreach ($cart->lines as $line)
                        <article wire:key="mobile-cart-line-{{ $line->id }}" class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800">
                            <div class="flex gap-4">
                                @if ($line->variant->product->media->first()?->url)
                                    <img src="{{ $line->variant->product->media->first()->url }}" alt="{{ $line->variant->product->title }}" class="h-24 w-24 shrink-0 rounded-xl object-cover" loading="lazy">
                                @else
                                    <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-2xl text-zinc-400 dark:bg-zinc-900" aria-hidden="true">⌂</div>
                                @endif

                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('product.show', $line->variant->product->handle) }}" class="font-semibold text-zinc-950 underline decoration-transparent underline-offset-4 hover:decoration-current focus:outline-none focus:ring-2 focus:ring-blue-600 dark:text-white" wire:navigate>{{ $line->variant->product->title }}</a>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $line->variant->title }}</p>
                                    <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">{{ $this->formatMoney($line->unit_price_amount) }} each</p>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between gap-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                                <div class="flex items-center gap-3" aria-label="Quantity for {{ $line->variant->product->title }}">
                                    <button type="button" wire:click="decrease({{ $line->id }})" wire:loading.attr="disabled" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-zinc-300 text-lg hover:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 disabled:opacity-50 dark:border-zinc-700" aria-label="Decrease quantity of {{ $line->variant->product->title }}">−</button>
                                    <span class="min-w-6 text-center font-medium" aria-live="polite">{{ $line->quantity }}</span>
                                    <button type="button" wire:click="increase({{ $line->id }})" wire:loading.attr="disabled" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-zinc-300 text-lg hover:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 disabled:opacity-50 dark:border-zinc-700" aria-label="Increase quantity of {{ $line->variant->product->title }}">+</button>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-zinc-950 dark:text-white">{{ $this->formatMoney($line->line_total_amount) }}</p>
                                    <button type="button" wire:click="remove({{ $line->id }})" wire:loading.attr="disabled" class="mt-1 text-sm font-medium text-red-600 underline underline-offset-4 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-600" aria-label="Remove {{ $line->variant->product->title }} from your cart">Remove</button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="hidden overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800 lg:block">
                    <table class="min-w-full text-left text-sm">
                        <caption class="sr-only">Products in your shopping cart</caption>
                        <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-5 py-4">Product</th>
                                <th scope="col" class="px-5 py-4">Price</th>
                                <th scope="col" class="px-5 py-4">Quantity</th>
                                <th scope="col" class="px-5 py-4 text-right">Total</th>
                                <th scope="col" class="px-5 py-4"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach ($cart->lines as $line)
                                <tr wire:key="desktop-cart-line-{{ $line->id }}">
                                    <td class="px-5 py-5">
                                        <div class="flex min-w-0 items-center gap-4">
                                            @if ($line->variant->product->media->first()?->url)
                                                <img src="{{ $line->variant->product->media->first()->url }}" alt="{{ $line->variant->product->title }}" class="h-16 w-16 shrink-0 rounded-xl object-cover" loading="lazy">
                                            @else
                                                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-xl text-zinc-400 dark:bg-zinc-900" aria-hidden="true">⌂</div>
                                            @endif
                                            <div class="min-w-0">
                                                <a href="{{ route('product.show', $line->variant->product->handle) }}" class="font-semibold text-zinc-950 underline decoration-transparent underline-offset-4 hover:decoration-current focus:outline-none focus:ring-2 focus:ring-blue-600 dark:text-white" wire:navigate>{{ $line->variant->product->title }}</a>
                                                <p class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $line->variant->title }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-5 text-zinc-700 dark:text-zinc-300">{{ $this->formatMoney($line->unit_price_amount) }}</td>
                                    <td class="px-5 py-5">
                                        <div class="flex items-center gap-3" aria-label="Quantity for {{ $line->variant->product->title }}">
                                            <button type="button" wire:click="decrease({{ $line->id }})" wire:loading.attr="disabled" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-zinc-300 text-lg hover:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 disabled:opacity-50 dark:border-zinc-700" aria-label="Decrease quantity of {{ $line->variant->product->title }}">−</button>
                                            <span class="min-w-5 text-center font-medium" aria-live="polite">{{ $line->quantity }}</span>
                                            <button type="button" wire:click="increase({{ $line->id }})" wire:loading.attr="disabled" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-zinc-300 text-lg hover:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 disabled:opacity-50 dark:border-zinc-700" aria-label="Increase quantity of {{ $line->variant->product->title }}">+</button>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-5 text-right font-semibold text-zinc-950 dark:text-white">{{ $this->formatMoney($line->line_total_amount) }}</td>
                                    <td class="px-5 py-5 text-right">
                                        <button type="button" wire:click="remove({{ $line->id }})" wire:loading.attr="disabled" class="text-sm font-medium text-red-600 underline underline-offset-4 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-600" aria-label="Remove {{ $line->variant->product->title }} from your cart">Remove</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="h-fit rounded-2xl bg-zinc-50 p-5 dark:bg-zinc-900 sm:p-6 lg:sticky lg:top-6" aria-labelledby="cart-summary-heading">
                <h2 id="cart-summary-heading" class="text-xl font-semibold text-zinc-950 dark:text-white">Order summary</h2>

                <form wire:submit="applyDiscount" class="mt-6">
                    <label for="cart-discount" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Discount code</label>
                    <div class="mt-2 flex gap-2">
                        <input id="cart-discount" type="text" wire:model.blur="discountCode" autocomplete="off" placeholder="Enter code" class="min-h-11 min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 placeholder:text-zinc-500 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" aria-describedby="cart-discount-error">
                        <button type="submit" wire:loading.attr="disabled" class="min-h-11 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold hover:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 disabled:opacity-50 dark:border-zinc-700">Apply</button>
                    </div>
                </form>

                @error('discountCode')
                    <p id="cart-discount-error" class="mt-2 text-sm text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>
                @enderror

                @if ($cart->discount_code)
                    <div class="mt-4 flex items-start justify-between gap-3 rounded-lg bg-green-50 px-3 py-3 text-sm dark:bg-green-950/40">
                        <p class="text-green-800 dark:text-green-200"><span class="font-semibold">{{ $cart->discount_code }}</span> applied</p>
                        <button type="button" wire:click="removeDiscount" class="shrink-0 font-medium text-green-800 underline underline-offset-4 hover:text-green-950 focus:outline-none focus:ring-2 focus:ring-green-700 dark:text-green-200">Remove</button>
                    </div>
                @endif

                <dl class="mt-6 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-600 dark:text-zinc-400">Subtotal</dt>
                        <dd class="font-medium text-zinc-950 dark:text-white">{{ $this->formatMoney($cart->lines->sum('line_subtotal_amount')) }}</dd>
                    </div>
                    @if ($discountAmount > 0)
                        <div class="flex justify-between gap-4 text-green-700 dark:text-green-300">
                            <dt>Discount</dt>
                            <dd class="font-medium">-{{ $this->formatMoney($discountAmount) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-600 dark:text-zinc-400">Shipping</dt>
                        <dd class="text-right text-zinc-600 dark:text-zinc-400">Calculated at checkout</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-zinc-200 pt-4 text-lg font-bold dark:border-zinc-700">
                        <dt class="text-zinc-950 dark:text-white">Total</dt>
                        <dd class="text-zinc-950 dark:text-white" aria-live="polite">{{ $this->formatMoney($totalAmount) }}</dd>
                    </div>
                </dl>
                <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">Shipping and taxes are calculated at checkout.</p>

                <button type="button" wire:click="checkout" wire:loading.attr="disabled" class="mt-6 inline-flex min-h-12 w-full items-center justify-center rounded-full bg-blue-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60 dark:focus:ring-offset-zinc-900">
                    <span wire:loading.remove wire:target="checkout">Checkout</span>
                    <span wire:loading wire:target="checkout">Starting checkout…</span>
                </button>
                <a href="{{ route('collections.index') }}" class="mt-4 block text-center text-sm font-medium text-zinc-600 underline underline-offset-4 hover:text-zinc-950 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:text-zinc-400 dark:hover:text-white" wire:navigate>Continue shopping</a>
            </aside>
        </div>
    @endif
</div>
