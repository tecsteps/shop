<div x-data="{ open: @entangle('open').live, trigger: null }" x-init="$watch('open', value => { document.documentElement.classList.toggle('overflow-hidden', value); if (value) { trigger = document.activeElement; $nextTick(() => $refs.close?.focus()) } else { trigger?.focus?.() } })" @keydown.escape.window="open = false">
    <div x-show="open" x-cloak class="fixed inset-0 z-[70]" role="dialog" aria-modal="true" aria-label="Shopping cart">
        <button type="button" class="absolute inset-0 bg-slate-950/55 backdrop-blur-[1px]" @click="open = false" aria-label="Close cart"></button>
        <section class="absolute inset-y-0 right-0 flex w-full flex-col bg-white shadow-2xl dark:bg-slate-950 sm:max-w-sm" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" @keydown.tab="const items=[...$el.querySelectorAll('button:not([disabled]),a[href],input:not([disabled]),select:not([disabled])')]; if(items.length){if($event.shiftKey && document.activeElement===items[0]){$event.preventDefault();items[items.length-1].focus()}else if(!$event.shiftKey && document.activeElement===items[items.length-1]){$event.preventDefault();items[0].focus()}}">
            <header class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <h2 class="text-lg font-semibold">Your Cart ({{ $this->cartItemCount }})</h2>
                <button x-ref="close" type="button" class="sf-icon-button" @click="open = false" aria-label="Close cart"><span aria-hidden="true" class="text-2xl leading-none">&times;</span></button>
            </header>

            @if (! $this->cart || $this->cart->lines->isEmpty())
                <div class="grid flex-1 place-items-center p-8 text-center">
                    <div><svg aria-hidden="true" class="mx-auto size-16 text-slate-200 dark:text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.3" d="M6 8h12l1 13H5L6 8Zm3 0V6a3 3 0 0 1 6 0v2"/></svg><p class="mt-5 font-medium">Your cart is empty</p><button type="button" @click="open = false" class="sf-button sf-button-secondary mt-5">Continue shopping</button></div>
                </div>
            @else
                <div class="flex-1 overflow-y-auto px-5" aria-live="polite">
                    @foreach ($this->cart->lines as $line)
                        @php($media = optional($line->variant->product->media->first())->storage_key)
                        <article wire:key="drawer-line-{{ $line->id }}" class="flex gap-4 border-b border-slate-200 py-5 dark:border-slate-800" wire:loading.class="opacity-50" wire:target="incrementLine({{ $line->id }}),decrementLine({{ $line->id }}),removeLine({{ $line->id }})">
                            <a href="{{ url('/products/'.$line->variant->product->handle) }}" wire:navigate class="size-16 shrink-0 overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800" @click="open = false">
                                @if ($media)<img src="{{ Storage::disk('public')->url($media) }}" alt="" class="h-full w-full object-cover">@endif
                            </a>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3"><div><a href="{{ url('/products/'.$line->variant->product->handle) }}" wire:navigate class="line-clamp-1 text-sm font-semibold hover:text-blue-700">{{ $line->variant->product->title }}</a><p class="mt-1 text-xs text-slate-500">{{ $line->variant->optionValues->pluck('value')->join(' / ') }}</p></div><x-storefront.price :amount="$line->line_total_amount" :currency="$this->cart->currency" /></div>
                                <div class="mt-3 flex items-center justify-between"><div class="inline-flex h-9 items-center rounded-lg border border-slate-300 dark:border-slate-700"><button wire:click="decrementLine({{ $line->id }})" class="grid size-9 place-items-center" aria-label="Decrease {{ $line->variant->product->title }} quantity">&minus;</button><span class="w-8 text-center text-sm" aria-label="Quantity">{{ $line->quantity }}</span><button wire:click="incrementLine({{ $line->id }})" class="grid size-9 place-items-center" aria-label="Increase {{ $line->variant->product->title }} quantity">+</button></div><button wire:click="removeLine({{ $line->id }})" class="min-h-11 text-xs font-medium text-slate-500 underline hover:text-red-700" aria-label="Remove {{ $line->variant->product->title }} from cart">Remove</button></div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <footer class="border-t border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-950">
                    @include('storefront.cart._discount')
                    <dl class="mt-5 space-y-2 text-sm"><div class="flex justify-between"><dt>Subtotal</dt><dd><x-storefront.price :amount="$this->cartSubtotal" :currency="$this->cart->currency" /></dd></div>@if($discountAmount > 0)<div class="flex justify-between text-emerald-700 dark:text-emerald-300"><dt>Discount ({{ $discountCode }})</dt><dd>-<x-storefront.price :amount="$discountAmount" :currency="$this->cart->currency" /></dd></div>@endif<div class="flex justify-between border-t border-slate-200 pt-3 text-base font-semibold dark:border-slate-800"><dt>Estimated total</dt><dd><x-storefront.price :amount="$this->cartTotal" :currency="$this->cart->currency" /></dd></div></dl>
                    <p class="mt-2 text-xs text-slate-500">Shipping and taxes calculated at checkout.</p>
                    <button wire:click="startCheckout" wire:loading.attr="disabled" wire:target="startCheckout" class="sf-button sf-button-primary mt-5 w-full"><span wire:loading.remove wire:target="startCheckout">Checkout</span><span wire:loading wire:target="startCheckout">Starting checkout...</span></button>
                    <a href="{{ url('/cart') }}" wire:navigate @click="open = false" class="mt-3 block min-h-11 py-3 text-center text-sm font-medium text-slate-600 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white">View full cart</a>
                </footer>
            @endif
        </section>
    </div>
</div>
