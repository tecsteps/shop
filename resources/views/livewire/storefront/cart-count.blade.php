<span aria-live="polite" aria-atomic="true">
    @if ($count > 0)
        <span class="absolute -top-0.5 -right-0.5 size-4 bg-blue-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
            {{ $count > 99 ? '99+' : $count }}
        </span>
        <span class="sr-only">{{ $count }} {{ Str::plural('item', $count) }} in cart</span>
    @endif
</span>
