@props([
    'title',
    'description' => null,
])

<x-admin.card {{ $attributes }}>
    <div class="grid gap-6 lg:grid-cols-[minmax(10rem,1fr)_minmax(0,2fr)] lg:gap-10">
        <div>
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $title }}</h2>
            @if (filled($description))
                <p class="mt-1.5 text-sm leading-6 text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
            @endif
        </div>

        <div class="min-w-0 space-y-5">{{ $slot }}</div>
    </div>
</x-admin.card>
