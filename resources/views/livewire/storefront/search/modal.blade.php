<div>
    {{-- Search Modal placeholder --}}
    @if($open)
        <div class="fixed inset-0 z-50 flex items-start justify-center pt-20" @keydown.escape.window="$wire.closeModal()">
            <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>
            <div class="relative w-full max-w-lg rounded-lg bg-white p-6 shadow-xl dark:bg-zinc-800">
                <form wire:submit="search">
                    <input type="text"
                           wire:model="query"
                           placeholder="Search products..."
                           autofocus
                           class="w-full rounded-md border-zinc-300 text-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                </form>
            </div>
        </div>
    @endif
</div>
