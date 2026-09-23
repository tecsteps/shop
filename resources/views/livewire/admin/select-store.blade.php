<div class="mx-auto w-full max-w-2xl">
    <div class="mb-7 text-center">
        <span class="mx-auto mb-4 flex size-12 items-center justify-center rounded-2xl bg-blue-600 text-white"><flux:icon name="shopping-bag" class="size-6" /></span>
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-white">Choose a store</h1>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Select the store you want to manage.</p>
    </div>

    <ul class="grid gap-3" aria-label="Stores you can manage">
        @foreach ($stores as $store)
            <li wire:key="store-choice-{{ $store->id }}">
                <button type="button" wire:click="select('{{ $store->id }}')" wire:loading.attr="disabled" class="flex min-h-16 w-full items-center justify-between gap-4 rounded-2xl border border-zinc-200 bg-white px-5 py-4 text-left shadow-sm transition hover:border-blue-300 hover:bg-blue-50/50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-60 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-blue-800 dark:hover:bg-blue-950/30">
                    <span><span class="block font-semibold text-zinc-950 dark:text-white">{{ $store->name }}</span><span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">Store ID {{ $store->id }}</span></span>
                    <flux:icon name="arrow-right" class="size-5 shrink-0 text-blue-600 dark:text-blue-400" />
                </button>
            </li>
        @endforeach
    </ul>

    <form method="POST" action="{{ route('admin.logout') }}" class="mt-6 text-center">@csrf<button type="submit" class="min-h-10 rounded-lg px-3 text-sm font-medium text-zinc-600 underline-offset-4 hover:text-zinc-950 hover:underline focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-zinc-300 dark:hover:text-white">Sign out</button></form>
</div>
