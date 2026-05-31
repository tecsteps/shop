@use(App\Support\Storefront\PriceFormatter)

<div>
    <div x-data="storefrontDialog($wire, 'open')" x-show="$wire.open" x-cloak
         x-on:keydown.tab="trapTab($event)"
         class="relative z-50" role="dialog" aria-modal="true" aria-label="{{ __('Search') }}">
        <div x-show="$wire.open" x-transition.opacity x-on:click="$wire.closeModal()"
             class="fixed inset-0 bg-zinc-900/50"></div>

        <div x-show="$wire.open"
             x-transition
             x-on:keydown.escape.window="$wire.closeModal()"
             class="fixed inset-x-0 top-0 mx-auto mt-16 w-full max-w-2xl px-4">
            <div class="overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">
                <div class="flex items-center gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                    <flux:icon.magnifying-glass class="size-5 text-zinc-400" />
                    <input type="search" wire:model.live.debounce.300ms="query"
                           x-effect="if ($wire.open) $nextTick(() => $el.focus())"
                           placeholder="{{ __('Search products and collections') }}"
                           class="flex-1 border-0 bg-transparent text-lg text-zinc-900 focus:outline-none focus:ring-0 dark:text-white"
                           aria-label="{{ __('Search') }}" />
                    <button type="button" wire:click="closeModal"
                            class="rounded-lg p-1 text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                            aria-label="{{ __('Close search') }}">
                        <flux:icon.x-mark class="size-5" />
                    </button>
                </div>

                <div class="max-h-[60vh] overflow-y-auto p-4" wire:loading.class="opacity-50" wire:target="query">
                    @if (strlen(trim($query)) >= 2 && $products->isEmpty())
                        <p class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __("No results for ':query'", ['query' => $query]) }}
                        </p>
                    @endif

                    @if ($products->isNotEmpty())
                        <h2 class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Products') }}</h2>
                        <ul class="mb-2">
                            @foreach ($products as $product)
                                @php $img = $product->primaryImage(); $price = $product->displayPriceAmount(); @endphp
                                <li wire:key="suggestion-{{ $product->id }}">
                                    <a href="/products/{{ $product->handle }}" wire:navigate
                                       class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        <span class="size-10 shrink-0 overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-800">
                                            @if ($img)
                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($img->storage_key) }}" alt="" class="h-full w-full object-cover" />
                                            @endif
                                        </span>
                                        <span class="min-w-0 flex-1 truncate text-sm text-zinc-900 dark:text-white">{{ $product->title }}</span>
                                        @if ($price !== null)
                                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ PriceFormatter::format($price, app('current_store')->default_currency) }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if (strlen(trim($query)) >= 2)
                        <a href="{{ route('storefront.search', ['q' => $query]) }}" wire:navigate
                           class="block rounded-lg px-3 py-2 text-sm font-medium text-blue-600 hover:bg-zinc-100 dark:text-blue-400 dark:hover:bg-zinc-800">
                            {{ __("View all results for ':query'", ['query' => $query]) }} &rarr;
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
