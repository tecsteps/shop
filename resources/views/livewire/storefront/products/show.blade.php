<div class="mx-auto max-w-7xl px-4 py-8">
    <x-storefront.breadcrumbs :items="array_filter([['label' => 'Home', 'url' => route('home')], $collection ? ['label' => $collection->title, 'url' => route('storefront.collections.show', ['handle' => $collection->handle])] : null, ['label' => $product->title]])" />

    <div class="mt-6 grid gap-8 lg:grid-cols-2">
        <section aria-label="Product images" class="lg:sticky lg:top-24 lg:self-start">
            @if ($product->productMedia->isNotEmpty())
                @php $main = $product->productMedia->get($selectedImage) ?? $product->productMedia->first(); @endphp
                <div class="aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($main->storage_key) }}" alt="{{ $main->alt_text ?? $product->title }}" class="h-full w-full object-cover" />
                </div>
                @if ($product->productMedia->count() > 1)
                    <div class="mt-3 flex gap-2 overflow-x-auto">
                        @foreach ($product->productMedia as $index => $media)
                            <button type="button" wire:key="thumb-{{ $media->getKey() }}" wire:click="selectImage({{ $index }})" aria-label="View image {{ $index + 1 }} of {{ $product->productMedia->count() }}" aria-current="{{ $index === $selectedImage ? 'true' : 'false' }}" class="h-16 w-16 shrink-0 overflow-hidden rounded border-2 {{ $index === $selectedImage ? 'border-blue-600' : 'border-transparent' }}">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($media->storage_key) }}" alt="" class="h-full w-full object-cover" loading="lazy" />
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="flex aspect-square items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.shopping-bag class="size-16 text-zinc-300 dark:text-zinc-700" variant="outline" />
                </div>
            @endif
        </section>

        <section aria-label="Product information">
            <h1 class="text-3xl font-bold">{{ $product->title }}</h1>

            <div class="mt-2" aria-live="polite">
                @if ($variant)
                    <x-storefront.price :amount="$variant->price_amount" :currency="$currency" :compareAtAmount="$variant->compare_at_amount" class="text-2xl" />
                @else
                    <p class="text-lg text-zinc-500">Select options to see the price</p>
                @endif
            </div>

            @foreach ($product->productOptions as $option)
                <fieldset wire:key="option-{{ $option->getKey() }}" class="mt-6">
                    <legend class="text-sm font-semibold">{{ $option->name }}</legend>
                    @if ($option->productOptionValues->count() > 6)
                        <select wire:model.live="selectedOptions.{{ $option->name }}" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" aria-label="{{ $option->name }}">
                            <option value="">Select {{ $option->name }}...</option>
                            @foreach ($option->productOptionValues as $value)
                                <option value="{{ $value->value }}">{{ $value->value }}</option>
                            @endforeach
                        </select>
                    @elseif (strtolower($option->name) === 'color')
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($option->productOptionValues as $value)
                                <label wire:key="color-{{ $value->getKey() }}" title="{{ $value->value }}" class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full border {{ ($selectedOptions[$option->name] ?? null) === $value->value ? 'ring-2 ring-blue-600 ring-offset-2' : 'border-zinc-300' }}">
                                    <input type="radio" wire:model.live="selectedOptions.{{ $option->name }}" value="{{ $value->value }}" class="sr-only" />
                                    <span aria-hidden="true">{{ mb_substr($value->value, 0, 1) }}</span>
                                    <span class="sr-only">{{ $value->value }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($option->productOptionValues as $value)
                                <label wire:key="opt-{{ $value->getKey() }}" class="cursor-pointer rounded-full border px-4 py-2 text-sm {{ ($selectedOptions[$option->name] ?? null) === $value->value ? 'border-blue-600 bg-blue-50 ring-1 ring-blue-600 dark:bg-blue-950' : 'border-zinc-300 hover:border-zinc-500' }}">
                                    <input type="radio" wire:model.live="selectedOptions.{{ $option->name }}" value="{{ $value->value }}" class="sr-only" />
                                    {{ $value->value }}
                                </label>
                            @endforeach
                        </div>
                    @endif
                </fieldset>
            @endforeach

            <div class="mt-4 text-sm" aria-live="polite">
                @if ($variant === null)
                    <p class="text-zinc-500">Select all options to check availability.</p>
                @elseif ($isBackorder)
                    <p class="text-blue-600 dark:text-blue-400">Available on backorder</p>
                @elseif ($available === null)
                    <p class="text-green-700 dark:text-green-400">In stock</p>
                @elseif ($available > 10)
                    <p class="text-green-700 dark:text-green-400">In stock</p>
                @elseif ($available > 0)
                    <p class="text-amber-600">Only {{ $available }} left in stock</p>
                @else
                    <p class="text-red-600">Out of stock</p>
                @endif
            </div>

            @if ($variant !== null)
                <div class="mt-4">
                    <x-storefront.quantity-selector :value="$quantity" :min="1" :max="$available" model="quantity" increment="incrementQuantity" decrement="decrementQuantity" />
                </div>

                @if ($available === 0)
                    <flux:button variant="ghost" disabled class="mt-4 w-full cursor-not-allowed opacity-50">Sold out</flux:button>
                @else
                    <flux:button wire:click="addToCart" variant="primary" wire:loading.attr="disabled" class="mt-4 w-full">
                        <span wire:loading.remove wire:target="addToCart">Add to cart</span>
                        <span wire:loading wire:target="addToCart">Adding...</span>
                    </flux:button>
                @endif
            @endif

            @if ($notice)
                <p class="mt-2 text-sm" role="status">{{ $notice }}</p>
            @endif

            @if ($product->description_html)
                <hr class="my-6 border-zinc-200 dark:border-zinc-800" />
                <div class="prose dark:prose-invert">{!! $product->description_html !!}</div>
            @endif

            @if (($product->tags ?? []) !== [])
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($product->tags as $tag)
                        <span wire:key="tag-{{ $loop->index }}" class="rounded-full bg-zinc-100 px-3 py-1 text-xs dark:bg-zinc-800">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
