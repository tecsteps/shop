<div @if (in_array($indexStatus, ['queued', 'processing'], true)) wire:poll.2s="refreshIndexStatus" @endif>
    @include('livewire.admin.components.page-heading', [
        'title' => 'Search settings',
        'description' => 'Tune how customers discover products in your storefront.',
        'breadcrumbs' => [['label' => 'Settings', 'href' => '/admin/settings']],
    ])

    <form wire:submit="save" class="max-w-4xl space-y-6">
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="synonyms-heading">
            <div>
                <h2 id="synonyms-heading" class="text-lg font-semibold">Synonyms</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Treat related words as equivalent when customers search.</p>
            </div>

            <div class="mt-5 space-y-3">
                @foreach ($synonymGroups as $index => $group)
                    <div wire:key="search-synonym-{{ $index }}" class="flex items-start gap-2">
                        <div class="min-w-0 flex-1">
                            <label for="search-synonym-{{ $index }}" class="sr-only">Synonym group {{ $index + 1 }}</label>
                            <input id="search-synonym-{{ $index }}" type="text" wire:model="synonymGroups.{{ $index }}" placeholder="t-shirt, tee, tshirt" class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            @error('synonymGroups.'.$index)<p class="mt-1 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
                        </div>
                        <button type="button" wire:click="removeSynonymGroup({{ $index }})" class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl text-zinc-500 hover:bg-rose-50 hover:text-rose-700 focus-visible:outline-2 focus-visible:outline-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-300" aria-label="Remove synonym group {{ $index + 1 }}">
                            <flux:icon name="trash" class="size-5" />
                        </button>
                    </div>
                @endforeach
                @error('synonymGroups')<p class="text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
            </div>

            <button type="button" wire:click="addSynonymGroup" class="mt-4 inline-flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-blue-700 hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-blue-300 dark:hover:bg-blue-950/40">
                <flux:icon name="plus" class="size-4" /> Add synonym group
            </button>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="stopwords-heading">
            <h2 id="stopwords-heading" class="text-lg font-semibold">Stop words</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Words that should not affect search results. Separate words with commas.</p>
            <label for="search-stop-words" class="sr-only">Stop words</label>
            <textarea id="search-stop-words" rows="4" wire:model="stopWords" placeholder="the, a, an, is, are..." class="mt-4 w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950"></textarea>
            @error('stopWords')<p class="mt-1 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="search-index-heading">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="search-index-heading" class="text-lg font-semibold">Search index</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Rebuild the product index after a bulk catalog change.</p>
                </div>
                <button type="button" wire:click="reindex" wire:loading.attr="disabled" wire:target="reindex" @disabled(in_array($indexStatus, ['queued', 'processing'], true)) class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl border border-zinc-300 px-4 text-sm font-semibold text-zinc-800 hover:bg-zinc-50 focus-visible:outline-2 focus-visible:outline-blue-600 disabled:cursor-wait disabled:opacity-60 dark:border-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-800">
                    <flux:icon name="arrow-path" class="size-4" />
                    <span wire:loading.remove wire:target="reindex">Reindex now</span>
                    <span wire:loading wire:target="reindex">Reindexing…</span>
                </button>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                <span>Index status: <strong class="font-semibold text-zinc-900 dark:text-zinc-100">{{ str($indexStatus)->headline() }}</strong></span>
                <span>{{ number_format($documentsCount) }} documents</span>
                @if ($lastReindexedAt)<span>Last indexed {{ \Illuminate\Support\Carbon::parse($lastReindexedAt)->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</span>@endif
            </div>

            @if (in_array($indexStatus, ['queued', 'processing'], true))
                <div class="mt-4" role="status" aria-live="polite">
                    <div class="mb-1 flex justify-between text-xs text-zinc-600 dark:text-zinc-400"><span>Reindexing products</span><span>{{ $progress }}% complete</span></div>
                    <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"><div class="h-full rounded-full bg-blue-600 transition-all" style="width: {{ $progress }}%"></div></div>
                    @if ($pendingUpdates > 0)<p class="mt-2 text-xs text-zinc-500">{{ number_format($pendingUpdates) }} remaining</p>@endif
                </div>
            @endif

            @error('reindex')<p class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror
        </section>

        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Save settings</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
