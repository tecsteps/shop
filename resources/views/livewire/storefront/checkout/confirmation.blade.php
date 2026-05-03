<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-semibold tracking-normal">Checkout saved</h1>
    <div class="mt-8 rounded-lg border border-zinc-200 p-6 dark:border-zinc-800">
        <p class="text-zinc-600 dark:text-zinc-400">Checkout #{{ $checkout->id }} is {{ str_replace('_', ' ', $checkout->status->value) }}.</p>
        <dl class="mt-5 flex flex-col gap-2 text-sm">
            <div class="flex justify-between"><dt>Email</dt><dd>{{ $checkout->email }}</dd></div>
            <div class="flex justify-between"><dt>Total</dt><dd>@include('storefront.components.price', ['amount' => $checkout->totals_json['total'] ?? 0, 'currency' => $checkout->totals_json['currency'] ?? $checkout->cart->currency])</dd></div>
        </dl>
        <a href="/collections" class="mt-5 inline-flex rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
            Continue shopping
        </a>
    </div>
</div>
