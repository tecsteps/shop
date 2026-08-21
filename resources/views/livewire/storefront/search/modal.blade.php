<div x-data="{ open: @entangle('open') }">
    <button type="button" wire:click="$set('open', true)" aria-label="Open search" class="text-sm font-semibold">Search</button>
    <div x-show="open" x-cloak class="fixed inset-0 z-50 bg-black/40 p-4" role="dialog" aria-modal="true" aria-label="Search">
        <div class="mx-auto mt-20 max-w-xl rounded-2xl bg-white p-5 shadow-xl dark:bg-zinc-900">
            <div class="flex gap-3"><input wire:model.live.debounce.200ms="query" autofocus type="search" placeholder="Search products" class="min-w-0 flex-1 rounded-full border border-zinc-300 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950"><button type="button" wire:click="$set('open', false)" class="rounded-full border px-4 py-3">Close</button></div>
            <div class="mt-4 space-y-2">@foreach ($suggestions as $suggestion)<a wire:key="suggestion-{{ $suggestion['id'] }}" href="{{ route('product.show', $suggestion['handle']) }}" class="block rounded-lg px-3 py-2 hover:bg-zinc-100" wire:navigate>{{ $suggestion['title'] }}</a>@endforeach</div>
        </div>
    </div>
</div>
