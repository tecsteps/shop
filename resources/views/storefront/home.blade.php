<x-layouts::storefront :title="$currentStore->name ?? null">
    <div class="flex flex-col gap-4">
        <h1 class="text-2xl font-semibold">{{ $currentStore->name }}</h1>
        <p class="text-zinc-600 dark:text-zinc-400">
            {{ __('Welcome to our store.') }}
        </p>
    </div>
</x-layouts::storefront>
