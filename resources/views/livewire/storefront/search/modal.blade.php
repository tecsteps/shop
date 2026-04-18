<div>
    @if ($open)
        <div role="dialog" aria-modal="true" aria-label="Search"
             class="fixed inset-0 z-40 flex items-start justify-center bg-black/50 p-6">
            <div class="mt-20 w-full max-w-xl rounded-lg border border-zinc-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center gap-2 border-b border-zinc-200 p-3 dark:border-zinc-800">
                    <flux:icon name="magnifying-glass" class="size-5 text-zinc-400" />
                    <input type="search"
                           wire:model.live.debounce.200ms="query"
                           class="w-full border-0 bg-transparent text-sm focus:ring-0 dark:text-zinc-100"
                           placeholder="Search products..." autofocus />
                    <flux:button variant="ghost" wire:click="toggle" icon="x-mark" aria-label="Close" />
                </div>
                <div class="max-h-80 overflow-y-auto p-2">
                    @if (trim($query) === '')
                        <p class="p-3 text-sm text-zinc-500 dark:text-zinc-400">Start typing to search.</p>
                    @elseif (empty($results))
                        <p class="p-3 text-sm text-zinc-500 dark:text-zinc-400">No products found.</p>
                    @else
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($results as $product)
                                <li wire:key="sm-{{ $product['id'] }}">
                                    <a href="{{ route('storefront.products.show', $product['handle']) }}"
                                       class="block px-3 py-2 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                        {{ $product['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
