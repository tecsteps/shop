<section class="rounded-3xl bg-slate-100 p-5 dark:bg-slate-900 sm:p-6" aria-labelledby="order-summary-heading">
    <h2 id="order-summary-heading" class="text-lg font-semibold">Order Summary</h2>
    <div class="mt-5 space-y-4">
        @foreach ($checkout->cart->lines as $line)
            @php($media = optional($line->variant->product->media->first())->storage_key)
            <div class="flex gap-3"><div class="relative size-14 shrink-0 rounded-xl bg-white dark:bg-slate-800">@if($media)<img src="{{ Storage::disk('public')->url($media) }}" alt="" class="h-full w-full rounded-xl object-cover">@endif<span class="absolute -right-2 -top-2 grid size-5 place-items-center rounded-full bg-slate-700 text-[10px] font-semibold text-white">{{ $line->quantity }}</span></div><div class="min-w-0 flex-1"><p class="line-clamp-1 text-sm font-semibold">{{ $line->variant->product->title }}</p><p class="mt-1 text-xs text-slate-500">{{ $line->variant->optionValues->pluck('value')->join(' / ') }}</p></div><div class="text-sm font-medium"><x-storefront.price :amount="$line->line_total_amount" :currency="$checkout->cart->currency" /></div></div>
        @endforeach
    </div>
    <div class="my-6 border-t border-slate-200 dark:border-slate-700"></div>
    @if ($discountCode !== '' && $checkout->discount_code)
        <div class="mb-5 flex min-h-11 items-center justify-between rounded-xl bg-emerald-50 px-3 text-sm text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200"><strong>{{ $discountCode }}</strong><button wire:click="removeDiscount" class="min-h-11 px-2 text-xs underline">Remove</button></div>
    @else
        <form wire:submit="applyDiscount" class="mb-5 flex gap-2"><label class="min-w-0 flex-1"><span class="sr-only">Discount code</span><input wire:model="discountCode" name="checkout_discount_code" class="sf-input w-full bg-white dark:bg-slate-950" placeholder="Discount code"></label><button class="sf-button sf-button-secondary bg-white dark:bg-slate-950">Apply</button></form>
        @if($discountError)<p class="-mt-3 mb-5 text-sm text-red-700 dark:text-red-300" role="alert">{{ $discountError }}</p>@endif
    @endif
    <dl class="space-y-3 text-sm" aria-live="polite"><div class="flex justify-between"><dt>Subtotal</dt><dd><x-storefront.price :amount="$this->totals['subtotal']" :currency="$checkout->cart->currency" /></dd></div>@if($this->totals['discount'] > 0)<div class="flex justify-between text-emerald-700 dark:text-emerald-300"><dt>Discount</dt><dd>-<x-storefront.price :amount="$this->totals['discount']" :currency="$checkout->cart->currency" /></dd></div>@endif<div class="flex justify-between"><dt>Shipping</dt><dd>@if($step < 4)Calculated next @elseif($this->totals['shipping'] === 0)Free @else<x-storefront.price :amount="$this->totals['shipping']" :currency="$checkout->cart->currency" />@endif</dd></div><div class="flex justify-between"><dt>Tax</dt><dd><x-storefront.price :amount="$this->totals['tax']" :currency="$checkout->cart->currency" /></dd></div><div class="flex justify-between border-t border-slate-300 pt-4 text-lg font-semibold dark:border-slate-700"><dt>Total</dt><dd><x-storefront.price :amount="$this->totals['total']" :currency="$checkout->cart->currency" /></dd></div></dl>
</section>
