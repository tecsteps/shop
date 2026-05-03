<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-semibold tracking-normal">Order confirmed</h1>
    <div class="mt-8 rounded-lg border border-zinc-200 p-6 dark:border-zinc-800">
        @if($checkout->order)
            <p class="text-zinc-600 dark:text-zinc-400">Order {{ $checkout->order->order_number }} is {{ str_replace('_', ' ', $checkout->order->financial_status->value) }}.</p>
            <dl class="mt-5 flex flex-col gap-2 text-sm">
                <div class="flex justify-between"><dt>Email</dt><dd>{{ $checkout->order->email }}</dd></div>
                <div class="flex justify-between"><dt>Total</dt><dd>@include('storefront.components.price', ['amount' => $checkout->order->total_amount, 'currency' => $checkout->order->currency])</dd></div>
                <div class="flex justify-between"><dt>Payment</dt><dd>{{ str_replace('_', ' ', $checkout->order->payment_method->value) }}</dd></div>
            </dl>

            @if($checkout->order->payment_method === \App\Enums\PaymentMethod::BankTransfer)
                <div class="mt-6 rounded-md border border-zinc-200 p-4 text-sm dark:border-zinc-800">
                    <h2 class="font-semibold tracking-normal">Bank transfer</h2>
                    <dl class="mt-3 flex flex-col gap-2">
                        <div class="flex justify-between gap-4"><dt>Bank</dt><dd>Mock Bank AG</dd></div>
                        <div class="flex justify-between gap-4"><dt>IBAN</dt><dd>DE89 3704 0044 0532 0130 00</dd></div>
                        <div class="flex justify-between gap-4"><dt>BIC</dt><dd>COBADEFFXXX</dd></div>
                        <div class="flex justify-between gap-4"><dt>Reference</dt><dd>{{ $checkout->order->order_number }}</dd></div>
                    </dl>
                </div>
            @endif
        @else
            <p class="text-zinc-600 dark:text-zinc-400">Checkout #{{ $checkout->id }} is {{ str_replace('_', ' ', $checkout->status->value) }}.</p>
        @endif
        <a href="/collections" class="mt-5 inline-flex rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
            Continue shopping
        </a>
    </div>
</div>
