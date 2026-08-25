<div>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-10">
            <aside class="hidden lg:block">
                <div class="sticky top-24">
                    @include('storefront.partials.account-nav')
                </div>
            </aside>

            <div>
                <div class="lg:hidden">
                    @include('storefront.partials.account-nav')
                </div>

                <div class="mt-6 lg:mt-0">
                    <x-storefront-breadcrumbs :items="[
                        ['label' => 'Account', 'url' => route('account.dashboard')],
                        ['label' => 'Orders', 'url' => route('account.orders.index')],
                        ['label' => $this->order->order_number],
                    ]" />
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">
                        Order {{ $this->order->order_number }}
                    </h1>
                    <div class="flex items-center gap-2">
                        <x-storefront-badge :text="ucfirst($this->order->status)" :variant="$this->statusBadgeVariant($this->order->status)" />
                        <x-storefront-badge :text="'Fulfillment: '.ucfirst($this->order->fulfillment_status ?? 'unfulfilled')" variant="muted" />
                    </div>
                </div>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    Placed on {{ $this->order->placed_at?->format('F j, Y') }}
                </p>

                {{-- Items --}}
                <section class="mt-8 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800" aria-label="Order items">
                    <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($this->orderLines as $line)
                            @php
                                $image = $line->variant?->product?->media->where('type', 'image')->first();
                            @endphp
                            <li class="flex items-start gap-4 px-5 py-4">
                                <span class="shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
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

                {{-- Shipping / billing / payment --}}
                <section class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3" aria-label="Order information">
                    <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Shipping address</h2>
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
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Billing address</h2>
                        @php
                            $billing = $this->order->billing_address_json ?? [];
                            $sameAsShipping = $billing == $shipping;
                        @endphp
                        @if ($sameAsShipping)
                            <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-300">Same as shipping</p>
                        @else
                            <address class="mt-3 space-y-1 text-sm not-italic text-zinc-700 dark:text-zinc-300">
                                <p>{{ $billing['first_name'] ?? '' }} {{ $billing['last_name'] ?? '' }}</p>
                                <p>{{ $billing['address1'] ?? '' }}</p>
                                <p>{{ $billing['city'] ?? '' }} {{ $billing['postal_code'] ?? '' }}</p>
                                <p>{{ $billing['country'] ?? '' }}</p>
                            </address>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Payment</h2>
                        <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-300">
                            {{ match ($this->order->payment_method) {
                                'paypal' => 'PayPal',
                                'bank_transfer' => 'Bank Transfer',
                                default => 'Credit Card',
                            } }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">Status: {{ $this->order->financial_status }}</p>
                    </div>
                </section>

                {{-- Totals --}}
                <section class="mt-6 rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800" aria-label="Order totals">
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-zinc-600 dark:text-zinc-400">Subtotal</dt>
                            <dd class="font-medium text-zinc-900 dark:text-white"><x-storefront-price :amount="$this->order->subtotal_amount" :currency="$this->currency" /></dd>
                        </div>
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
                        @if ($this->order->discount_amount > 0)
                            <div class="flex items-center justify-between">
                                <dt class="text-emerald-600 dark:text-emerald-400">Discount</dt>
                                <dd class="font-medium text-emerald-600 dark:text-emerald-400">-<x-storefront-price :amount="$this->order->discount_amount" :currency="$this->currency" /></dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between border-t border-zinc-200 pt-3 dark:border-zinc-800">
                            <dt class="text-base font-semibold text-zinc-900 dark:text-white">Total</dt>
                            <dd class="text-base font-semibold text-zinc-900 dark:text-white"><x-storefront-price :amount="$this->order->total_amount" :currency="$this->currency" /></dd>
                        </div>
                    </dl>
                </section>

                {{-- Fulfillment --}}
                @if ($this->order->fulfillments->isNotEmpty())
                    <section class="mt-6 space-y-4" aria-label="Fulfillment">
                        @foreach ($this->order->fulfillments as $fulfillment)
                            <div class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-800">
                                <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Fulfillment</h2>
                                <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-300">
                                    @if ($fulfillment->tracking_company)
                                        Shipped via {{ $fulfillment->tracking_company }}
                                        @if ($fulfillment->tracking_number)
                                            - {{ $fulfillment->tracking_number }}
                                        @endif
                                    @else
                                        Fulfillment status: {{ $fulfillment->status }}
                                    @endif
                                </p>
                                @if ($fulfillment->tracking_url)
                                    <a
                                        href="{{ $fulfillment->tracking_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 transition hover:underline dark:text-blue-400"
                                    >
                                        Track shipment
                                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M7 7h10v10" /><path d="M7 17 17 7" />
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif

                {{-- Timeline --}}
                @if ($this->timeline->isNotEmpty())
                    <section class="mt-8" aria-label="Order timeline">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Order timeline</h2>
                        <ol class="mt-5 space-y-5 border-l-2 border-zinc-200 pl-5 dark:border-zinc-700">
                            @foreach ($this->timeline as $event)
                                <li class="relative">
                                    <span class="absolute -left-[27px] flex size-4 items-center justify-center rounded-full border-2 {{ $event['done'] ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/60' : 'border-zinc-300 bg-white dark:border-zinc-600 dark:bg-zinc-900' }}" aria-hidden="true">
                                        @if ($event['done'])
                                            <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                        @endif
                                    </span>
                                    <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $event['title'] }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $event['at']?->format('M j, Y H:i') }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif
            </div>
        </div>
    </div>
</div>
