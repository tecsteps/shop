@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
])

<header {{ $attributes->class('space-y-4') }}>
    @if (collect($breadcrumbs)->isNotEmpty())
        <x-admin.breadcrumbs :items="$breadcrumbs" />
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 sm:text-3xl dark:text-white">{{ $title }}</h1>

            @if (filled($description))
                <p class="mt-1.5 max-w-3xl text-sm leading-6 text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</header>
