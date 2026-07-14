<div>
    @if ($discountCode !== '' && ($discountAmount > 0 || $freeShippingDiscount))
        <div class="flex min-h-11 items-center justify-between rounded-xl bg-emerald-50 px-3 text-sm text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200"><span class="font-semibold">{{ $discountCode }} {{ $freeShippingDiscount ? '(Free shipping)' : '' }}</span><button wire:click="removeDiscount" class="min-h-11 px-2 text-xs underline">Remove</button></div>
    @else
        <form wire:submit="applyDiscount" class="flex gap-2"><label class="min-w-0 flex-1"><span class="sr-only">Discount code</span><input name="discount_code" wire:model="discountCode" class="sf-input w-full" placeholder="Discount code" autocomplete="off"></label><button class="sf-button sf-button-secondary shrink-0" wire:loading.attr="disabled" wire:target="applyDiscount"><span wire:loading.remove wire:target="applyDiscount">Apply</span><span wire:loading wire:target="applyDiscount">Applying...</span></button></form>
        @if ($discountError)<p class="mt-2 text-sm text-red-700 dark:text-red-300" role="alert">{{ $discountError }}</p>@endif
        @error('discountCode')<p class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
    @endif
</div>
