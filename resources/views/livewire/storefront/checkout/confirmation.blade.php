<div class="mx-auto max-w-2xl">
    <div class="rounded-2xl bg-white p-10 text-center ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800" data-testid="order-confirmation">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-3xl font-bold">Thank you!</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Your order <strong data-testid="order-number">{{ $order->order_number }}</strong> has been placed.</p>

        <div class="mt-6 rounded-xl bg-zinc-50 p-6 text-left dark:bg-zinc-800">
            <div class="space-y-2">
                @foreach ($order->lines as $line)
                    <div class="flex justify-between text-sm">
                        <span>{{ $line->title_snapshot }} × {{ $line->quantity }}</span>
                        <span>{{ $order->currency }} {{ number_format($line->total_amount / 100, 2) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-3 border-t border-zinc-200 pt-3 text-sm dark:border-zinc-700">
                <div class="flex justify-between"><span>Subtotal</span><span>{{ $order->currency }} {{ number_format($order->subtotal_amount / 100, 2) }}</span></div>
                @if ($order->discount_amount > 0)<div class="flex justify-between text-emerald-600"><span>Discount</span><span>−{{ $order->currency }} {{ number_format($order->discount_amount / 100, 2) }}</span></div>@endif
                <div class="flex justify-between"><span>Shipping</span><span>{{ $order->currency }} {{ number_format($order->shipping_amount / 100, 2) }}</span></div>
                <div class="flex justify-between"><span>Tax</span><span>{{ $order->currency }} {{ number_format($order->tax_amount / 100, 2) }}</span></div>
                <div class="mt-2 flex justify-between border-t border-zinc-200 pt-2 font-semibold dark:border-zinc-700"><span>Total</span><span>{{ $order->currency }} {{ number_format($order->total_amount / 100, 2) }}</span></div>
            </div>
        </div>

        <a href="{{ route('storefront.home') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900" wire:navigate>
            Continue shopping
        </a>
    </div>
</div>
