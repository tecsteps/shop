<div>
    @if($open)
        <div class="fixed inset-0 z-50 flex items-start justify-center pt-20" @keydown.escape.window="$wire.closeModal()">
            <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>
            <div class="relative w-full max-w-lg rounded-lg bg-white p-6 shadow-xl dark:bg-zinc-800">
                <form wire:submit="search">
                    <input type="text"
                           wire:model.live.debounce.300ms="query"
                           placeholder="Search products..."
                           autofocus
                           class="w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                </form>

                @if($suggestions->isNotEmpty())
                    <ul class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($suggestions as $suggestion)
                            <li>
                                <a href="/products/{{ $suggestion->handle }}"
                                   class="flex items-center gap-3 px-2 py-2 text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700/50">
                                    <svg class="h-4 w-4 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                                    </svg>
                                    {{ $suggestion->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @elseif(mb_strlen($query) >= 2)
                    <p class="mt-3 px-2 text-sm text-zinc-500 dark:text-zinc-400">No results found for "{{ $query }}"</p>
                @endif

                @if(mb_strlen($query) >= 2)
                    <div class="mt-3 border-t border-zinc-100 pt-3 dark:border-zinc-700">
                        <a href="/search?q={{ urlencode($query) }}"
                           class="flex items-center gap-2 text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                            </svg>
                            Search for "{{ $query }}"
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
