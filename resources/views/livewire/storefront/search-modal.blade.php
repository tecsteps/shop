<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4" role="dialog" aria-modal="true" aria-label="Search">
            <button type="button" wire:click="closeModal" class="absolute inset-0 h-full w-full cursor-default bg-black/50" aria-label="Close search" tabindex="-1"></button>
            <div class="relative mt-16 w-full max-w-xl rounded-lg bg-white p-4 shadow-xl dark:bg-zinc-950">
                <div class="flex items-center gap-2">
                    <flux:input wire:model.live.debounce.300ms="query" type="search" placeholder="Search products and collections..." aria-label="Search" class="flex-1" autofocus />
                    <button type="button" wire:click="closeModal" class="flex h-11 w-11 items-center justify-center rounded hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Close search">x</button>
                </div>
                @if (trim($query) !== '')
                    @if ($suggestions->isEmpty())
                        <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">No results for '{{ $query }}'</p>
                    @else
                        <ul class="mt-4 flex max-h-80 flex-col gap-1 overflow-y-auto" role="listbox" aria-label="Search suggestions">
                            @foreach ($suggestions as $suggestion)
                                <li wire:key="suggest-{{ $suggestion['type'] }}-{{ $loop->index }}" role="option" aria-selected="false">
                                    <a href="{{ $suggestion['type'] === 'product' ? route('storefront.products.show', ['handle' => $suggestion['handle']]) : route('storefront.collections.show', ['handle' => $suggestion['handle']]) }}" class="flex items-center gap-3 rounded p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                        @if (($suggestion['image_url'] ?? null))
                                            <img src="{{ $suggestion['image_url'] }}" alt="" class="h-10 w-10 rounded object-cover" loading="lazy" />
                                        @endif
                                        <span class="flex-1 text-sm">{{ $suggestion['title'] }}</span>
                                        @if ($suggestion['type'] === 'product')
                                            <span class="text-sm text-zinc-500">{{ \App\Support\Money::format($suggestion['price_amount'], $suggestion['currency']) }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('storefront.search', ['q' => $query]) }}" class="mt-3 block text-sm text-blue-600 hover:underline dark:text-blue-400">View all results</a>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>
