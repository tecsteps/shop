@extends('storefront.layout')

@section('title', 'Checkout #'.$checkout->id.' | '.($currentStore->name ?? config('app.name')))

@section('content')
    <section class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @if ($errors->any())
                <div class="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Checkout #{{ $checkout->id }}</h1>
                <p class="mt-2 text-sm text-slate-600">Status: <span class="font-medium text-slate-800">{{ str_replace('_', ' ', $status) }}</span></p>
                @if (is_string($checkout->email) && $checkout->email !== '')
                    <p class="mt-1 text-sm text-slate-600">Email: {{ $checkout->email }}</p>
                @endif
            </div>

            @php($shippingAddress = is_array(old('shipping_address')) ? old('shipping_address') : (is_array($checkout->shipping_address_json) ? $checkout->shipping_address_json : []))
            @php($billingAddress = is_array(old('billing_address')) ? old('billing_address') : (is_array($checkout->billing_address_json) ? $checkout->billing_address_json : []))
            @php($useShippingAsBilling = old('use_shipping_as_billing', ! is_array($checkout->billing_address_json) || $checkout->billing_address_json === $checkout->shipping_address_json))

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">1. Contact & Address</h2>

                <form method="POST" action="{{ route('storefront.checkout.address.update', ['checkoutId' => $checkout->id]) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $checkout->email) }}"
                            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900"
                        />
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label for="shipping_first_name" class="block text-sm font-medium text-slate-700">First name</label>
                            <input id="shipping_first_name" name="shipping_address[first_name]" type="text" value="{{ $shippingAddress['first_name'] ?? '' }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                        </div>
                        <div>
                            <label for="shipping_last_name" class="block text-sm font-medium text-slate-700">Last name</label>
                            <input id="shipping_last_name" name="shipping_address[last_name]" type="text" value="{{ $shippingAddress['last_name'] ?? '' }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                        </div>
                    </div>

                    <div>
                        <label for="shipping_address1" class="block text-sm font-medium text-slate-700">Address line 1</label>
                        <input id="shipping_address1" name="shipping_address[address1]" type="text" value="{{ $shippingAddress['address1'] ?? '' }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                    </div>

                    <div>
                        <label for="shipping_address2" class="block text-sm font-medium text-slate-700">Address line 2</label>
                        <input id="shipping_address2" name="shipping_address[address2]" type="text" value="{{ $shippingAddress['address2'] ?? '' }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label for="shipping_city" class="block text-sm font-medium text-slate-700">City</label>
                            <input id="shipping_city" name="shipping_address[city]" type="text" value="{{ $shippingAddress['city'] ?? '' }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                        </div>
                        <div>
                            <label for="shipping_postal_code" class="block text-sm font-medium text-slate-700">Postal code</label>
                            <input id="shipping_postal_code" name="shipping_address[postal_code]" type="text" value="{{ $shippingAddress['postal_code'] ?? '' }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-3">
                        <div>
                            <label for="shipping_country" class="block text-sm font-medium text-slate-700">Country</label>
                            <input id="shipping_country" name="shipping_address[country]" type="text" value="{{ $shippingAddress['country'] ?? '' }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                        </div>
                        <div>
                            <label for="shipping_country_code" class="block text-sm font-medium text-slate-700">Country code</label>
                            <input id="shipping_country_code" name="shipping_address[country_code]" type="text" value="{{ $shippingAddress['country_code'] ?? '' }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm uppercase text-slate-900" />
                        </div>
                        <div>
                            <label for="shipping_phone" class="block text-sm font-medium text-slate-700">Phone</label>
                            <input id="shipping_phone" name="shipping_address[phone]" type="text" value="{{ $shippingAddress['phone'] ?? '' }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label for="shipping_province" class="block text-sm font-medium text-slate-700">Province/State</label>
                            <input id="shipping_province" name="shipping_address[province]" type="text" value="{{ $shippingAddress['province'] ?? '' }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                        </div>
                        <div>
                            <label for="shipping_province_code" class="block text-sm font-medium text-slate-700">Province code</label>
                            <input id="shipping_province_code" name="shipping_address[province_code]" type="text" value="{{ $shippingAddress['province_code'] ?? '' }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm uppercase text-slate-900" />
                        </div>
                    </div>

                    <input type="hidden" name="use_shipping_as_billing" value="0" />
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="use_shipping_as_billing" value="1" @checked((bool) $useShippingAsBilling) class="rounded border-slate-300 text-slate-900 focus:ring-slate-500" />
                        Use shipping address as billing address
                    </label>

                    @if (! (bool) $useShippingAsBilling)
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                            <p class="text-sm font-medium text-slate-800">Billing address</p>
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <input name="billing_address[first_name]" type="text" value="{{ $billingAddress['first_name'] ?? '' }}" placeholder="First name" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                                <input name="billing_address[last_name]" type="text" value="{{ $billingAddress['last_name'] ?? '' }}" placeholder="Last name" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                                <input name="billing_address[address1]" type="text" value="{{ $billingAddress['address1'] ?? '' }}" placeholder="Address line 1" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 md:col-span-2" />
                                <input name="billing_address[city]" type="text" value="{{ $billingAddress['city'] ?? '' }}" placeholder="City" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                                <input name="billing_address[postal_code]" type="text" value="{{ $billingAddress['postal_code'] ?? '' }}" placeholder="Postal code" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                                <input name="billing_address[country]" type="text" value="{{ $billingAddress['country'] ?? '' }}" placeholder="Country" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                                <input name="billing_address[country_code]" type="text" value="{{ $billingAddress['country_code'] ?? '' }}" placeholder="Country code" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm uppercase text-slate-900" />
                            </div>
                        </div>
                    @endif

                    <button type="submit" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                        Save address
                    </button>
                </form>
            </div>

            @php($selectedShippingMethod = (int) old('shipping_method_id', (int) ($checkout->shipping_method_id ?? 0)))
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">2. Shipping Method</h2>

                <form method="POST" action="{{ route('storefront.checkout.shipping-method.update', ['checkoutId' => $checkout->id]) }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')

                    @if (! $requiresShipping)
                        <p class="text-sm text-slate-600">No shipping is required for this cart.</p>
                    @elseif ($shippingMethods === [])
                        <p class="text-sm text-slate-600">Add a valid shipping address to see available shipping methods.</p>
                    @else
                        @foreach ($shippingMethods as $method)
                            <label class="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2 text-sm">
                                <span class="flex items-center gap-2">
                                    <input type="radio" name="shipping_method_id" value="{{ (int) $method['id'] }}" @checked($selectedShippingMethod === (int) $method['id']) />
                                    <span class="font-medium text-slate-800">{{ $method['name'] }}</span>
                                </span>
                                <span class="text-slate-700">
                                    {{ number_format(((int) $method['amount']) / 100, 2, '.', ',') }} {{ strtoupper((string) $method['currency']) }}
                                </span>
                            </label>
                        @endforeach
                    @endif

                    <button type="submit" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                        Save shipping method
                    </button>
                </form>
            </div>

            @php($selectedPaymentMethod = (string) old('payment_method', (string) ($checkout->payment_method?->value ?? '')))
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">3. Payment Method</h2>

                <form method="POST" action="{{ route('storefront.checkout.payment-method.update', ['checkoutId' => $checkout->id]) }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')

                    @foreach ($paymentMethods as $value => $label)
                        <label class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-800">
                            <input type="radio" name="payment_method" value="{{ $value }}" @checked($selectedPaymentMethod === $value) />
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach

                    <button type="submit" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                        Save payment method
                    </button>
                </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">4. Discount</h2>

                <form method="POST" action="{{ route('storefront.checkout.discount.apply', ['checkoutId' => $checkout->id]) }}" class="mt-4 flex gap-2">
                    @csrf
                    <input
                        name="code"
                        type="text"
                        value="{{ old('code', $checkout->discount_code) }}"
                        placeholder="Discount code"
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900"
                    />
                    <button type="submit" class="inline-flex items-center rounded-md border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-100">
                        Apply
                    </button>
                </form>

                @if (is_string($checkout->discount_code) && $checkout->discount_code !== '')
                    <div class="mt-3 flex items-center justify-between rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                        <p>
                            Applied: <span class="font-semibold">{{ $checkout->discount_code }}</span>
                            @if ($appliedDiscount)
                                ({{ str_replace('_', ' ', $appliedDiscount->value_type->value) }})
                            @endif
                        </p>
                        <form method="POST" action="{{ route('storefront.checkout.discount.remove', ['checkoutId' => $checkout->id]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-medium text-emerald-900 hover:text-emerald-700">Remove</button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">5. Complete Payment</h2>

                <form method="POST" action="{{ route('storefront.checkout.pay', ['checkoutId' => $checkout->id]) }}" class="mt-4 space-y-3">
                    @csrf

                    <div>
                        <label for="pay_payment_method" class="block text-sm font-medium text-slate-700">Payment method</label>
                        <select id="pay_payment_method" name="payment_method" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900">
                            <option value="">Use selected checkout method</option>
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}" @selected($selectedPaymentMethod === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <input name="card_holder" type="text" value="{{ old('card_holder') }}" placeholder="Card holder (optional)" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                        <input name="card_number" type="text" value="{{ old('card_number') }}" placeholder="Card number (optional)" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                        <input name="card_expiry" type="text" value="{{ old('card_expiry') }}" placeholder="MM/YY (optional)" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                        <input name="card_cvc" type="text" value="{{ old('card_cvc') }}" placeholder="CVC (optional)" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                    </div>

                    <button type="submit" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                        Complete payment
                    </button>
                </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Items</h2>

                @php($lines = $checkout->cart?->lines ?? collect())

                @if ($lines->isEmpty())
                    <p class="mt-3 text-sm text-slate-600">This checkout currently has no cart lines.</p>
                @else
                    <ul class="mt-3 space-y-2">
                        @foreach ($lines as $line)
                            @php($product = $line->variant?->product)
                            <li class="flex items-start justify-between rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $product?->title ?? 'Unavailable product' }}</p>
                                    <p class="text-slate-600">Quantity: {{ (int) $line->quantity }}</p>
                                </div>
                                <p class="font-medium text-slate-800">
                                    {{ number_format(((int) $line->line_total_amount) / 100, 2, '.', ',') }} {{ strtoupper((string) $totals['currency']) }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <aside class="h-fit rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Totals</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex items-center justify-between text-slate-600">
                    <dt>Subtotal</dt>
                    <dd>{{ number_format(((int) $totals['subtotal_amount']) / 100, 2, '.', ',') }} {{ strtoupper((string) $totals['currency']) }}</dd>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <dt>Discounts</dt>
                    <dd>-{{ number_format(((int) $totals['discount_amount']) / 100, 2, '.', ',') }} {{ strtoupper((string) $totals['currency']) }}</dd>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <dt>Shipping</dt>
                    <dd>{{ number_format(((int) $totals['shipping_amount']) / 100, 2, '.', ',') }} {{ strtoupper((string) $totals['currency']) }}</dd>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <dt>Tax</dt>
                    <dd>{{ number_format(((int) $totals['tax_amount']) / 100, 2, '.', ',') }} {{ strtoupper((string) $totals['currency']) }}</dd>
                </div>
                <div class="flex items-center justify-between border-t border-slate-200 pt-2 font-semibold text-slate-900">
                    <dt>Total</dt>
                    <dd>{{ number_format(((int) $totals['total_amount']) / 100, 2, '.', ',') }} {{ strtoupper((string) $totals['currency']) }}</dd>
                </div>
            </dl>
        </aside>
    </section>
@endsection
