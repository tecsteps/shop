<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[['label' => 'Cart']]" />

    <div class="mt-8 flex flex-col gap-8 lg:grid lg:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">Cart</h1>
                    <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $lineCount }} {{ Str::plural('item', $lineCount) }}</p>
                </div>

                <flux:button :href="route('collections.index')" wire:navigate variant="ghost" icon="arrow-left">
                    Continue shopping
                </flux:button>
            </div>

            @if ($lines->isEmpty())
                <div class="rounded-lg border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                    <flux:icon name="shopping-bag" class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                    <h2 class="mt-4 text-lg font-semibold text-zinc-950 dark:text-white">Your cart is empty</h2>
                    <flux:button :href="route('collections.index')" wire:navigate variant="primary" class="mt-6">
                        Browse products
                    </flux:button>
                </div>
            @else
                <div class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 bg-white dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-950">
                    @foreach ($lines as $line)
                        <div class="grid gap-4 p-4 sm:grid-cols-[5rem_1fr_auto] sm:items-center" wire:key="cart-page-line-{{ $line->getKey() }}">
                            <a href="{{ route('products.show', $line->variant->product->handle) }}" wire:navigate class="flex aspect-square items-center justify-center rounded-md border border-zinc-200 bg-zinc-100 text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-600">
                                <flux:icon name="shopping-bag" class="size-7" />
                            </a>

                            <div class="min-w-0 space-y-2">
                                <a href="{{ route('products.show', $line->variant->product->handle) }}" wire:navigate class="font-medium text-zinc-950 hover:underline dark:text-white">
                                    {{ $line->variant->product->title }}
                                </a>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $line->variant->optionValues->map(fn ($value) => $value->option->name.': '.$value->value)->implode(' / ') ?: ($line->variant->sku ?: 'Default') }}
                                </p>
                                <x-storefront.price :amount="$line->unit_price_amount" :currency="$cart->currency" class="text-sm" />
                            </div>

                            <div class="flex items-center justify-between gap-4 sm:justify-end">
                                <div class="inline-flex h-10 items-center rounded-md border border-zinc-200 dark:border-zinc-700">
                                    <button type="button" wire:click="decreaseQuantity({{ $line->getKey() }})" class="flex size-10 items-center justify-center border-r border-zinc-200 text-sm dark:border-zinc-700" aria-label="Decrease {{ $line->variant->product->title }} quantity">-</button>
                                    <span class="w-12 text-center text-sm tabular-nums">{{ $line->quantity }}</span>
                                    <button type="button" wire:click="increaseQuantity({{ $line->getKey() }})" class="flex size-10 items-center justify-center border-l border-zinc-200 text-sm dark:border-zinc-700" aria-label="Increase {{ $line->variant->product->title }} quantity">+</button>
                                </div>

                                <div class="min-w-24 text-right">
                                    <x-storefront.price :amount="$line->line_total_amount" :currency="$cart->currency" class="justify-end" />
                                    <flux:button wire:click="removeLine({{ $line->getKey() }})" variant="ghost" size="sm" icon="trash" class="mt-2" aria-label="Remove {{ $line->variant->product->title }}" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <aside class="h-fit rounded-lg border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Summary</h2>

            <div class="mt-5 space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-zinc-600 dark:text-zinc-400">Subtotal</span>
                    @if ($cart)
                        <x-storefront.price :amount="$subtotal" :currency="$cart->currency" />
                    @else
                        <span class="font-semibold text-zinc-950 dark:text-white">-</span>
                    @endif
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-zinc-600 dark:text-zinc-400">Discount</span>
                    @if ($cart && ($discountAmount > 0 || $discountFreeShipping))
                        <span class="font-semibold text-zinc-950 dark:text-white">
                            {{ $discountFreeShipping && $discountAmount === 0 ? 'Free shipping' : '-'.\App\Support\Money::format($discountAmount, $cart->currency) }}
                        </span>
                    @else
                        <span class="font-medium text-zinc-950 dark:text-white">-</span>
                    @endif
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-zinc-600 dark:text-zinc-400">Shipping</span>
                    @if (! $cart || $lineCount === 0)
                        <span class="font-medium text-zinc-950 dark:text-white">-</span>
                    @elseif (! $requiresShipping || $discountFreeShipping)
                        <span class="font-medium text-zinc-950 dark:text-white">Free</span>
                    @elseif ($estimatedShipping !== null)
                        <x-storefront.price :amount="$estimatedShipping" :currency="$cart->currency" />
                    @else
                        <span class="font-medium text-zinc-950 dark:text-white">Unavailable</span>
                    @endif
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-zinc-200 pt-3 text-base dark:border-zinc-800">
                    <span class="font-semibold text-zinc-950 dark:text-white">Estimated total</span>
                    @if ($cart)
                        <x-storefront.price :amount="$estimatedTotal" :currency="$cart->currency" />
                    @else
                        <span class="font-semibold text-zinc-950 dark:text-white">-</span>
                    @endif
                </div>
            </div>

            @if ($cart && $lineCount > 0)
                <form wire:submit="applyDiscount" class="mt-5 border-t border-zinc-200 pt-5 dark:border-zinc-800">
                    <div class="flex items-end gap-2">
                        <div class="min-w-0 flex-1">
                            <flux:input wire:model="discountCode" label="Discount code" placeholder="SAVE10" />
                        </div>
                        <flux:button type="submit" variant="ghost" icon="tag" class="shrink-0">
                            Apply
                        </flux:button>
                    </div>
                    <flux:error name="discountCode" />

                    @if ($appliedDiscountCode)
                        <div class="mt-3 flex items-center justify-between gap-3 text-sm">
                            <flux:badge color="green">{{ $appliedDiscountCode }}</flux:badge>
                            <flux:button type="button" wire:click="removeDiscount" variant="ghost" size="sm" icon="x-mark">
                                Remove
                            </flux:button>
                        </div>
                    @endif
                </form>

                <form wire:submit="estimateShipping" class="mt-5 border-t border-zinc-200 pt-5 dark:border-zinc-800">
                    <div class="grid gap-3">
                        <flux:select wire:model.live="shippingCountry" label="Ship to">
                            <flux:select.option value="DE">Germany</flux:select.option>
                            <flux:select.option value="AT">Austria</flux:select.option>
                            <flux:select.option value="CH">Switzerland</flux:select.option>
                        </flux:select>

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                            <flux:input wire:model.live.debounce.400ms="shippingPostalCode" label="Postal code" placeholder="10115" />
                            <flux:input wire:model.live.debounce.400ms="shippingProvinceCode" label="Region code" placeholder="BE" />
                        </div>
                    </div>

                    <flux:error name="shippingCountry" />

                    <flux:button type="submit" variant="ghost" icon="truck" class="mt-4 w-full">
                        Estimate shipping
                    </flux:button>

                    <div class="mt-4 divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                        @forelse ($rates as $rate)
                            <div class="flex items-center justify-between gap-4 py-2 first:pt-0" wire:key="cart-rate-{{ $rate->getKey() }}">
                                <span class="font-medium text-zinc-950 dark:text-white">{{ $rate->name }}</span>
                                <x-storefront.price :amount="data_get($rateAmounts, $rate->getKey(), 0)" :currency="$cart->currency" class="justify-end text-sm" />
                            </div>
                        @empty
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $requiresShipping ? 'No rates are available.' : 'No shipping needed.' }}
                            </p>
                        @endforelse
                    </div>
                </form>
            @endif

            <flux:button wire:click="checkout" variant="primary" class="mt-6 w-full" :disabled="$lineCount === 0">
                Checkout
            </flux:button>
        </aside>
    </div>
</section>
