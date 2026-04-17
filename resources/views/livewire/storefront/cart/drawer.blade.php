<div class="text-sm">
    @if ($cart === null || $lines->isEmpty())
        <span class="text-neutral-500">Cart (0)</span>
    @else
        <a href="{{ url('/cart') }}" class="text-neutral-700 hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-white">
            Cart ({{ $cart->totalQuantity() }})
        </a>
    @endif
</div>
