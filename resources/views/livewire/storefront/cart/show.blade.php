<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Your Cart') }}</h1>

    @if ($lines === [])
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <svg class="size-20 text-zinc-300 dark:text-zinc-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" />
            </svg>
            <p class="mt-4 text-zinc-500 dark:text-zinc-400">{{ __('Your cart is empty') }}</p>
            <a
                href="{{ route('home') }}"
                class="mt-6 rounded-lg border border-zinc-300 px-5 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
            >
                {{ __('Continue shopping') }}
            </a>
        </div>
    @else
        @if ($cartError !== null)
            <p class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950/50 dark:text-red-400" role="alert">{{ $cartError }}</p>
        @endif

        <div class="mt-8 lg:grid lg:grid-cols-3 lg:gap-10">
            {{-- Desktop table / mobile cards --}}
            <div class="lg:col-span-2">
                <table class="hidden w-full md:table">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-800">
                            <th class="pb-3 text-left text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400" colspan="2">{{ __('Product') }}</th>
                            <th class="pb-3 text-left text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Price') }}</th>
                            <th class="pb-3 text-left text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Quantity') }}</th>
                            <th class="pb-3 text-right text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Total') }}</th>
                            <th class="pb-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($lines as $line)
                            <tr wire:key="cart-line-{{ $line['id'] }}" wire:loading.class="opacity-50" wire:target="updateQuantity, removeLine">
                                <td class="py-4 pr-4">
                                    <div class="size-16 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-900">
                                        @if ($line['image_url'] !== null)
                                            <img src="{{ $line['image_url'] }}" alt="" class="size-full object-cover" loading="lazy" />
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 pr-4">
                                    @if ($line['handle'] !== null)
                                        <a href="{{ route('storefront.products.show', $line['handle']) }}" class="text-sm font-semibold text-zinc-900 hover:underline dark:text-white">{{ $line['title'] }}</a>
                                    @else
                                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $line['title'] }}</span>
                                    @endif
                                    @if ($line['variant_label'] !== '')
                                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $line['variant_label'] }}</p>
                                    @endif
                                </td>
                                <td class="py-4 pr-4">
                                    <x-storefront.price :amount="$line['unit_price_amount']" :currency="$currency" class="text-sm" />
                                </td>
                                <td class="py-4 pr-4">
                                    <div class="inline-flex items-stretch overflow-hidden rounded-lg border border-zinc-300 dark:border-zinc-700">
                                        <button
                                            type="button"
                                            wire:click="updateQuantity({{ $line['id'] }}, {{ $line['quantity'] - 1 }})"
                                            class="flex size-9 items-center justify-center text-zinc-600 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                            aria-label="{{ __('Decrease quantity') }}"
                                        >
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M5 12h14" /></svg>
                                        </button>
                                        <span class="flex h-9 w-11 items-center justify-center border-x border-zinc-300 text-sm font-medium text-zinc-900 dark:border-zinc-700 dark:text-white">{{ $line['quantity'] }}</span>
                                        <button
                                            type="button"
                                            wire:click="updateQuantity({{ $line['id'] }}, {{ $line['quantity'] + 1 }})"
                                            class="flex size-9 items-center justify-center text-zinc-600 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                            aria-label="{{ __('Increase quantity') }}"
                                        >
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="py-4 text-right">
                                    <x-storefront.price :amount="$line['line_total_amount']" :currency="$currency" class="text-sm" />
                                </td>
                                <td class="py-4 pl-4 text-right">
                                    <button
                                        type="button"
                                        wire:click="removeLine({{ $line['id'] }})"
                                        class="rounded p-1.5 text-zinc-400 transition hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-zinc-500 dark:hover:text-red-400"
                                        aria-label="{{ __('Remove :item from cart', ['item' => $line['title']]) }}"
                                    >
                                        <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Mobile cards --}}
                <ul class="divide-y divide-zinc-200 md:hidden dark:divide-zinc-800">
                    @foreach ($lines as $line)
                        <li class="flex gap-3 py-4" wire:key="cart-line-m-{{ $line['id'] }}">
                            <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-900">
                                @if ($line['image_url'] !== null)
                                    <img src="{{ $line['image_url'] }}" alt="" class="size-full object-cover" loading="lazy" />
                                @endif
                            </div>
                            <div class="flex min-w-0 flex-1 flex-col">
                                <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $line['title'] }}</p>
                                @if ($line['variant_label'] !== '')
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $line['variant_label'] }}</p>
                                @endif
                                <div class="mt-2 flex items-center justify-between gap-2">
                                    <div class="inline-flex items-stretch overflow-hidden rounded-lg border border-zinc-300 dark:border-zinc-700">
                                        <button type="button" wire:click="updateQuantity({{ $line['id'] }}, {{ $line['quantity'] - 1 }})" class="flex size-8 items-center justify-center text-zinc-600 dark:text-zinc-300" aria-label="{{ __('Decrease quantity') }}">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M5 12h14" /></svg>
                                        </button>
                                        <span class="flex h-8 w-9 items-center justify-center border-x border-zinc-300 text-xs font-medium text-zinc-900 dark:border-zinc-700 dark:text-white">{{ $line['quantity'] }}</span>
                                        <button type="button" wire:click="updateQuantity({{ $line['id'] }}, {{ $line['quantity'] + 1 }})" class="flex size-8 items-center justify-center text-zinc-600 dark:text-zinc-300" aria-label="{{ __('Increase quantity') }}">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                                        </button>
                                    </div>
                                    <x-storefront.price :amount="$line['line_total_amount']" :currency="$currency" class="text-sm" />
                                </div>
                                <div class="flex justify-end pt-1">
                                    <button type="button" wire:click="removeLine({{ $line['id'] }})" class="rounded p-1 text-zinc-400 hover:text-red-600 dark:text-zinc-500 dark:hover:text-red-400" aria-label="{{ __('Remove :item from cart', ['item' => $line['title']]) }}">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Summary --}}
            <div class="mt-8 lg:mt-0">
                <div class="rounded-2xl bg-zinc-50 p-6 dark:bg-zinc-900">
                    {{-- Discount code --}}
                    @if ($discount !== null)
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-green-700 dark:text-green-400">
                                {{ $discount['code'] }}
                                @if ($discount['free_shipping'])
                                    ({{ __('Free shipping') }})
                                @endif
                            </span>
                            <button type="button" wire:click="removeDiscount" class="text-xs font-medium text-zinc-500 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                {{ __('Remove') }}
                            </button>
                        </div>
                    @else
                        <form wire:submit="applyDiscount" class="flex gap-2">
                            <label for="cart-discount-code" class="sr-only">{{ __('Discount code') }}</label>
                            <input
                                id="cart-discount-code"
                                type="text"
                                wire:model="discountCode"
                                placeholder="{{ __('Discount code') }}"
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500"
                            />
                            <button type="submit" class="shrink-0 rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                <span wire:loading.remove wire:target="applyDiscount">{{ __('Apply') }}</span>
                                <span wire:loading wire:target="applyDiscount">{{ __('Applying...') }}</span>
                            </button>
                        </form>
                        @if ($discountError !== null)
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400" role="alert">{{ $discountError }}</p>
                        @endif
                    @endif

                    <dl class="mt-5 space-y-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-800" aria-live="polite">
                        <div class="flex items-center justify-between">
                            <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Subtotal') }}</dt>
                            <dd><x-storefront.price :amount="$subtotalAmount" :currency="$currency" class="text-sm" /></dd>
                        </div>
                        @if ($discount !== null && $discount['amount'] > 0)
                            <div class="flex items-center justify-between text-green-700 dark:text-green-400">
                                <dt>{{ __('Discount') }} ({{ $discount['code'] }})</dt>
                                <dd class="font-medium">-{{ \App\Support\Storefront\PriceFormatter::format($discount['amount'], $currency) }}</dd>
                            </div>
                        @endif
                        @if ($shippingEstimate !== null)
                            <div class="flex items-center justify-between">
                                <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Shipping estimate') }} ({{ $shippingEstimate['name'] }})</dt>
                                <dd><x-storefront.price :amount="$discount !== null && $discount['free_shipping'] ? 0 : $shippingEstimate['amount']" :currency="$currency" class="text-sm" /></dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between border-t border-zinc-200 pt-3 text-base dark:border-zinc-800">
                            <dt class="font-semibold text-zinc-900 dark:text-white">{{ __('Estimated total') }}</dt>
                            <dd><x-storefront.price :amount="$estimatedTotal" :currency="$currency" /></dd>
                        </div>
                    </dl>

                    @if ($requiresShipping)
                        <div class="mt-4">
                            <label for="estimate-country" class="mb-1.5 block text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ __('Estimate shipping for') }}</label>
                            <select
                                id="estimate-country"
                                wire:model.live="estimateCountry"
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                            >
                                <option value="">{{ __('Select a country') }}</option>
                                @foreach (['DE' => 'Germany', 'AT' => 'Austria', 'BE' => 'Belgium', 'FR' => 'France', 'IT' => 'Italy', 'NL' => 'Netherlands', 'ES' => 'Spain', 'PL' => 'Poland', 'GB' => 'United Kingdom', 'US' => 'United States', 'CA' => 'Canada', 'AU' => 'Australia'] as $code => $country)
                                    <option value="{{ $code }}">{{ $country }}</option>
                                @endforeach
                            </select>
                            @if ($estimateCountry !== '' && $shippingEstimate === null)
                                <p class="mt-1.5 text-xs text-amber-600 dark:text-amber-400">{{ __('No shipping methods are available for this country.') }}</p>
                            @endif
                        </div>
                    @endif

                    <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Shipping and taxes calculated at checkout') }}</p>

                    <a
                        href="{{ route('storefront.checkout') }}"
                        class="mt-5 block w-full rounded-lg px-4 py-3 text-center text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"
                        style="background-color: var(--sf-primary, #2563eb);"
                    >
                        {{ __('Checkout') }}
                    </a>
                    <a
                        href="{{ route('home') }}"
                        class="mt-3 block w-full text-center text-sm text-zinc-500 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                    >
                        {{ __('Continue shopping') }}
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
