<div class="sf-container sf-page-y">
    <x-storefront.breadcrumbs :items="[['label' => 'Home', 'url' => url('/')], ['label' => 'Your Cart']]" />
    <h1 class="sf-page-title mt-7">Your Cart</h1>

    @if (! $this->cart || $this->cart->lines->isEmpty())
        <div class="sf-empty-state mt-10"><svg aria-hidden="true" class="mx-auto size-16 text-slate-200 dark:text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="1.3" d="M6 8h12l1 13H5L6 8Zm3 0V6a3 3 0 0 1 6 0v2"/></svg><h2 class="mt-5 text-xl font-semibold">Your cart is empty</h2><p class="mt-2 text-slate-500">Find something you love and it will appear here.</p><a href="{{ url('/collections') }}" wire:navigate class="sf-button sf-button-primary mt-6">Continue shopping</a></div>
    @else
        <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <section aria-label="Cart items" class="divide-y divide-slate-200 border-y border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                @foreach ($this->cart->lines as $line)
                    @php($media = optional($line->variant->product->media->first())->storage_key)
                    <article wire:key="cart-line-{{ $line->id }}" class="grid grid-cols-[5rem_minmax(0,1fr)] gap-4 py-6 sm:grid-cols-[6rem_minmax(0,1fr)_auto]" wire:loading.class="opacity-50" wire:target="incrementLine({{ $line->id }}),decrementLine({{ $line->id }}),removeLine({{ $line->id }})">
                        <a href="{{ url('/products/'.$line->variant->product->handle) }}" wire:navigate class="aspect-square overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800">@if($media)<img src="{{ Storage::disk('public')->url($media) }}" alt="" class="h-full w-full object-cover">@endif</a>
                        <div><a href="{{ url('/products/'.$line->variant->product->handle) }}" wire:navigate class="font-semibold hover:text-blue-700">{{ $line->variant->product->title }}</a><p class="mt-1 text-sm text-slate-500">{{ $line->variant->optionValues->pluck('value')->join(' / ') }}</p><p class="mt-2 text-sm"><x-storefront.price :amount="$line->unit_price_amount" :currency="$this->cart->currency" /></p><div class="mt-4 inline-flex h-11 items-center rounded-xl border border-slate-300 dark:border-slate-700"><button wire:click="decrementLine({{ $line->id }})" class="grid size-11 place-items-center" aria-label="Decrease {{ $line->variant->product->title }} quantity">&minus;</button><span class="w-10 text-center" aria-label="Quantity">{{ $line->quantity }}</span><button wire:click="incrementLine({{ $line->id }})" class="grid size-11 place-items-center" aria-label="Increase {{ $line->variant->product->title }} quantity">+</button></div></div>
                        <div class="col-start-2 flex items-center justify-between sm:col-start-3 sm:block sm:text-right"><strong><x-storefront.price :amount="$line->line_total_amount" :currency="$this->cart->currency" /></strong><button wire:click="removeLine({{ $line->id }})" class="ml-5 min-h-11 text-sm text-slate-500 underline hover:text-red-700" aria-label="Remove {{ $line->variant->product->title }} from cart">Remove</button></div>
                    </article>
                @endforeach
            </section>

            <aside class="sf-card h-fit lg:sticky lg:top-28" aria-labelledby="cart-summary-heading">
                <h2 id="cart-summary-heading" class="text-lg font-semibold">Order summary</h2>
                <div class="mt-5">@include('storefront.cart._discount')</div>
                <dl class="mt-6 space-y-3 text-sm"><div class="flex justify-between"><dt>Subtotal</dt><dd><x-storefront.price :amount="$this->cartSubtotal" :currency="$this->cart->currency" /></dd></div>@if($discountAmount > 0)<div class="flex justify-between text-emerald-700 dark:text-emerald-300"><dt>Discount</dt><dd>-<x-storefront.price :amount="$discountAmount" :currency="$this->cart->currency" /></dd></div>@endif<div class="flex justify-between border-t border-slate-200 pt-4 text-base font-semibold dark:border-slate-800"><dt>Total</dt><dd><x-storefront.price :amount="$this->cartTotal" :currency="$this->cart->currency" /></dd></div></dl>
                <p class="mt-2 text-xs text-slate-500">Shipping and taxes calculated at checkout.</p>
                <button wire:click="startCheckout" class="sf-button sf-button-primary mt-6 w-full" wire:loading.attr="disabled" wire:target="startCheckout"><span wire:loading.remove wire:target="startCheckout">Checkout</span><span wire:loading wire:target="startCheckout">Starting checkout...</span></button>
                <a href="{{ url('/collections') }}" wire:navigate class="mt-3 block min-h-11 py-3 text-center text-sm font-medium text-slate-600 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white">Continue shopping</a>
            </aside>
        </div>
    @endif
</div>
