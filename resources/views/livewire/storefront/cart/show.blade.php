<div class="mx-auto max-w-5xl px-4 py-8">
    <h1 class="text-3xl font-bold">Your Cart</h1>

    @if ($cartNotice)
        <p class="mt-2 text-sm" role="status">{{ $cartNotice }}</p>
    @endif

    @if ($lines === [])
        <div class="flex flex-col items-center gap-3 py-16 text-center">
            <flux:icon.shopping-bag class="size-16 text-zinc-300 dark:text-zinc-700" variant="outline" />
            <p class="text-zinc-500 dark:text-zinc-400">Your cart is empty</p>
            <a href="{{ route('home') }}" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">Continue shopping</a>
        </div>
    @else
        <div class="mt-6 hidden md:block">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wide text-zinc-500">
                        <th scope="col" class="py-2">Product</th>
                        <th scope="col" class="py-2">Price</th>
                        <th scope="col" class="py-2">Quantity</th>
                        <th scope="col" class="py-2 text-right">Total</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($lines as $line)
                        <tr wire:key="cart-row-{{ $line['id'] }}">
                            <td class="py-4 pr-4">
                                <div class="flex items-center gap-3">
                                    @if ($line['image_url'])
                                        <img src="{{ $line['image_url'] }}" alt="" class="h-16 w-16 rounded object-cover" loading="lazy" />
                                    @endif
                                    <div>
                                        <p class="font-semibold">{{ $line['title'] }}</p>
                                        @if ($line['variant'] !== '')
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $line['variant'] }}</p>
                                        @endif
                                        @if ($line['is_backorder'] ?? false)
                                            <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">Available on backorder</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 pr-4">{{ \App\Support\Money::format($line['unit_price'], $line['currency']) }}</td>
                            <td class="py-4 pr-4">
                                <div class="inline-flex items-center rounded border border-zinc-300 dark:border-zinc-700">
                                    <button type="button" wire:click="decrementLine({{ $line['id'] }})" wire:loading.attr="disabled" class="flex h-10 w-10 items-center justify-center" aria-label="Decrease quantity">-</button>
                                    <span class="w-10 text-center">{{ $line['quantity'] }}</span>
                                    <button type="button" wire:click="incrementLine({{ $line['id'] }})" wire:loading.attr="disabled" class="flex h-10 w-10 items-center justify-center" aria-label="Increase quantity">+</button>
                                </div>
                            </td>
                            <td class="py-4 text-right font-semibold">{{ \App\Support\Money::format($line['total'], $line['currency']) }}</td>
                            <td class="py-4 text-right">
                                <button type="button" wire:click="removeLine({{ $line['id'] }})" class="text-zinc-400 hover:text-red-600" aria-label="Remove {{ $line['title'] }} from cart">x</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <ul class="mt-6 flex flex-col gap-4 md:hidden">
            @foreach ($lines as $line)
                <li wire:key="cart-card-{{ $line['id'] }}" class="flex gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                    @if ($line['image_url'])
                        <img src="{{ $line['image_url'] }}" alt="" class="h-16 w-16 shrink-0 rounded object-cover" loading="lazy" />
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{{ $line['title'] }}</p>
                        <p class="text-sm font-semibold">{{ \App\Support\Money::format($line['total'], $line['currency']) }}</p>
                        @if ($line['is_backorder'] ?? false)
                            <p class="text-xs text-blue-600 dark:text-blue-400">Available on backorder</p>
                        @endif
                        <div class="mt-2 flex items-center justify-between">
                            <div class="inline-flex items-center rounded border border-zinc-300 dark:border-zinc-700">
                                <button type="button" wire:click="decrementLine({{ $line['id'] }})" wire:loading.attr="disabled" class="flex h-8 w-8 items-center justify-center" aria-label="Decrease quantity">-</button>
                                <span class="w-8 text-center text-sm">{{ $line['quantity'] }}</span>
                                <button type="button" wire:click="incrementLine({{ $line['id'] }})" wire:loading.attr="disabled" class="flex h-8 w-8 items-center justify-center" aria-label="Increase quantity">+</button>
                            </div>
                            <button type="button" wire:click="removeLine({{ $line['id'] }})" class="text-sm text-zinc-400 hover:text-red-600" aria-label="Remove {{ $line['title'] }} from cart">Remove</button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-6 flex flex-col items-stretch gap-2 md:items-end">
            @if ($appliedCode)
                <p class="flex items-center justify-between gap-4 text-sm text-green-700 md:justify-end dark:text-green-400">
                    <span>{{ $appliedCode }} applied</span>
                    <button type="button" wire:click="removeDiscount" class="underline">Remove</button>
                </p>
            @else
                <div class="flex gap-2 md:justify-end">
                    <flux:input wire:model="discountCodeInput" placeholder="Discount code" aria-label="Discount code" />
                    <flux:button wire:click="applyDiscount" variant="ghost" wire:loading.attr="disabled">Apply</flux:button>
                </div>
                @error('discountCodeInput') <p class="text-sm text-red-600 md:text-right">{{ $message }}</p> @enderror
            @endif
            <p class="flex justify-between gap-8 text-sm md:justify-end">Subtotal: <span>{{ \App\Support\Money::format($totals['subtotal'], $totals['currency']) }}</span></p>
            @if ($totals['discount'] > 0)
                <p class="flex justify-between gap-8 text-sm text-green-700 md:justify-end dark:text-green-400">Discount: <span>-{{ \App\Support\Money::format($totals['discount'], $totals['currency']) }}</span></p>
            @endif
            <p class="flex justify-between gap-8 font-semibold md:justify-end">Total: <span>{{ \App\Support\Money::format($totals['total'], $totals['currency']) }}</span></p>
            <flux:button wire:click="startCheckout" variant="primary">Checkout</flux:button>
            <a href="{{ route('home') }}" class="text-sm text-zinc-500 hover:underline dark:text-zinc-400">Continue shopping</a>
        </div>
    @endif
</div>
