<div>
    <button type="button" wire:click="show" class="rounded-md px-2 py-2 text-sm font-medium hover:bg-zinc-100 dark:hover:bg-zinc-900 sm:px-3">
        Search
    </button>

    @if($isOpen)
        <div class="fixed inset-0 z-50 bg-zinc-950/50 px-4 py-6 backdrop-blur-sm sm:px-6" wire:click.self="close">
            <div class="mx-auto max-w-2xl overflow-hidden rounded-lg bg-white shadow-2xl dark:bg-zinc-950">
                <div class="flex items-center gap-3 border-b border-zinc-200 p-4 dark:border-zinc-800">
                    <label class="flex-1">
                        <span class="sr-only">Search products</span>
                        <input wire:model.live.debounce.150ms="q" type="search" autofocus placeholder="Search products" class="w-full rounded-md border border-zinc-300 bg-white px-4 py-3 text-base text-zinc-950 outline-none focus:border-zinc-950 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:focus:border-white">
                    </label>
                    <button type="button" wire:click="close" class="rounded-md px-3 py-2 text-sm font-semibold text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-900 dark:hover:text-white">
                        Close
                    </button>
                </div>

                <div class="max-h-[70vh] overflow-y-auto p-4">
                    @if(trim($q) === '')
                        <div class="rounded-md border border-zinc-200 p-5 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                            Start typing to search products and collections.
                        </div>
                    @elseif($suggestions->isEmpty())
                        <div class="rounded-md border border-zinc-200 p-5 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                            No suggestions found.
                        </div>
                    @else
                        <div class="grid gap-2">
                            @foreach($suggestions as $suggestion)
                                <a wire:key="search-suggestion-{{ $suggestion['type'] }}-{{ $loop->index }}" href="{{ $suggestion['url'] }}" class="rounded-md border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900">
                                    <div class="text-sm font-semibold">{{ $suggestion['title'] }}</div>
                                    @if($suggestion['subtitle'])
                                        <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $suggestion['subtitle'] }}</div>
                                    @endif
                                </a>
                            @endforeach
                        </div>

                        <a href="{{ route('storefront.search.index', ['q' => $q]) }}" class="mt-4 inline-flex rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                            View all results
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
