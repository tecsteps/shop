<div>
    <div class="mx-auto max-w-2xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="text-center">
            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                    <path d="m9 11 3 3L22 4" />
                </svg>
            </span>

            <h1 class="mt-6 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Thank you for your order!</h1>
            <p class="mt-3 text-lg text-zinc-600 dark:text-zinc-300">Order {{ $this->order->order_number }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                We have sent a confirmation to {{ $this->order->email }}.
            </p>
        </div>

        {{-- Items --}}
        <section class="mt-10 rounded-2xl border border-zinc-200 dark:border-zinc-800" aria-label="Order summary">
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($this->orderLines as $line)
                    <li class="flex items-start gap-4 px-5 py-4">
                        <span class="shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            @php
                                $image = $line->variant?->product?->media->where('type', 'image')->first();
                            @endphp
                            @if ($image)
                                <img src="{{ Storage::url($image->storage_key) }}" alt="" class="size-14 object-cover" />
                            @else
                                <span class="flex size-14 items-center justify-center text-zinc-300 dark:text-zinc-600">
                                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" /><path d="M3 6h18" /><path d="M16 10a4 4 0 0 1-8 0" /></svg>
                                </span>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $line->title_snapshot }}</p>
                            @if ($line->variant?->optionValues->isNotEmpty())
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $line->variant->optionValues->sortBy(fn ($value) => $value->option?->position ?? 0)->pluck('value')->join(' / ') }}
                                </p>
                            @endif
                            <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">Qty {{ $line->quantity }}</p>
                        </div>
                        <x-storefront-price :amount="$line->total_amount" :currency="$this->currency" class="text-sm" />
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Bank transfer instructions --}}
        @if ($this->order->payment_method === 'bank_transfer')
            <section class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-6 dark:border-blue-900 dark:bg-blue-950/40" aria-label="Bank transfer instructions">
                <h2 class="flex items-center gap-2 text-base font-semibold text-blue-800 dark:text-blue-200">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
                    </svg>
                    Bank Transfer Instructions
                </h2>
                <p class="mt-3 text-sm text-blue-700 dark:text-blue-300">
                    Please transfer the total amount to the following account:
                </p>
                <dl class="mt-4 space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <div class="flex justify-between gap-4"><dt class="font-medium">Bank:</dt><dd>Mock Bank AG</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-medium">IBAN:</dt><dd class="font-mono">DE89 3704 0044 0532 0130 00</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-medium">BIC:</dt><dd class="font-mono">COBADEFFXXX</dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-medium">Amount:</dt><dd><x-storefront-price :amount="$this->order->total_amount" :currency="$this->currency" /></dd></div>
                    <div class="flex justify-between gap-4"><dt class="font-medium">Reference:</dt><dd class="font-mono">{{ $this->order->order_number }}</dd></div>
                </dl>
                <p class="mt-4 text-sm text-blue-700 dark:text-blue-300">
                    Please complete your transfer within 7 days. Your order will be processed once payment is confirmed by our team.
                </p>
            </section>
        @endif

        {{-- Address + payment --}}
        <section class="mt-6 grid gap-6 sm:grid-cols-2">
            <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Shipping address</h2>
                @php
                    $shipping = $this->order->shipping_address_json ?? [];
                @endphp
                <address class="mt-3 space-y-1 text-sm not-italic text-zinc-700 dark:text-zinc-300">
                    <p>{{ $shipping['first_name'] ?? '' }} {{ $shipping['last_name'] ?? '' }}</p>
                    <p>{{ $shipping['address1'] ?? '' }}</p>
                    @if (! empty($shipping['address2']))
                        <p>{{ $shipping['address2'] }}</p>
                    @endif
                    <p>{{ $shipping['city'] ?? '' }}{{ ! empty($shipping['province']) ? ', '.$shipping['province'] : '' }} {{ $shipping['postal_code'] ?? '' }}</p>
                    <p>{{ $shipping['country'] ?? '' }}</p>
                </address>
            </div>

            <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Payment method</h2>
                <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $this->paymentMethodLabel }}</p>
            </div>
        </section>

        {{-- Totals --}}
        <section class="mt-6 rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800" aria-label="Order totals">
            <dl class="space-y-2.5 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-zinc-600 dark:text-zinc-400">Subtotal</dt>
                    <dd class="font-medium text-zinc-900 dark:text-white"><x-storefront-price :amount="$this->order->subtotal_amount" :currency="$this->currency" /></dd>
                </div>
                @if ($this->order->discount_amount > 0)
                    <div class="flex items-center justify-between">
                        <dt class="text-emerald-600 dark:text-emerald-400">Discount</dt>
                        <dd class="font-medium text-emerald-600 dark:text-emerald-400">-<x-storefront-price :amount="$this->order->discount_amount" :currency="$this->currency" /></dd>
                    </div>
                @endif
                <div class="flex items-center justify-between">
                    <dt class="text-zinc-600 dark:text-zinc-400">Shipping</dt>
                    <dd class="font-medium text-zinc-900 dark:text-white"><x-storefront-price :amount="$this->order->shipping_amount" :currency="$this->currency" /></dd>
                </div>
                @if ($this->order->tax_amount > 0)
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-600 dark:text-zinc-400">Tax</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white"><x-storefront-price :amount="$this->order->tax_amount" :currency="$this->currency" /></dd>
                    </div>
                @endif
                <div class="flex items-center justify-between border-t border-zinc-200 pt-3 dark:border-zinc-800">
                    <dt class="text-base font-semibold text-zinc-900 dark:text-white">Total</dt>
                    <dd class="text-base font-semibold text-zinc-900 dark:text-white"><x-storefront-price :amount="$this->order->total_amount" :currency="$this->currency" /></dd>
                </div>
            </dl>
        </section>

        {{-- Actions --}}
        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a
                href="{{ route('storefront.home') }}"
                class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-blue-700 sm:w-auto dark:bg-blue-500 dark:hover:bg-blue-400"
            >
                Continue shopping
            </a>
            @if ($this->showViewOrder)
                <a
                    href="{{ route('account.orders.show', ['orderNumber' => $this->order->order_number]) }}"
                    class="inline-flex w-full items-center justify-center rounded-lg border border-zinc-300 px-6 py-3.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 sm:w-auto dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-900"
                >
                    View order
                </a>
            @endif
        </div>
    </div>
</div>
