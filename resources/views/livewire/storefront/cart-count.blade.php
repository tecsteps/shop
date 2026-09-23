<button type="button" wire:click="$dispatch('open-cart-drawer')" class="relative inline-flex size-11 items-center justify-center rounded-full text-stone-700 hover:bg-stone-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:text-stone-200 dark:hover:bg-stone-800" aria-label="Open shopping cart, {{ $itemCount }} items">
    @include('storefront.components.icon', ['name' => 'bag', 'class' => 'size-5'])
    <span data-cart-count class="{{ $itemCount > 0 ? '' : 'hidden' }} absolute right-0.5 top-0.5 inline-flex min-w-5 items-center justify-center rounded-full bg-blue-700 px-1 text-[10px] font-semibold leading-5 text-white dark:bg-blue-400 dark:text-stone-950" aria-live="polite">{{ $itemCount }}</span>
</button>
