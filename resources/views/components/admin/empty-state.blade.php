@props([
    'title',
    'description' => null,
])

<div {{ $attributes->class('admin-empty-state') }}>
    @isset($icon)
        <div class="mx-auto mb-5 w-fit text-zinc-300 dark:text-zinc-700">{{ $icon }}</div>
    @else
        <svg aria-hidden="true" class="mx-auto mb-5 size-12 text-zinc-300 dark:text-zinc-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25">
            <path d="M4.75 6.75 12 3l7.25 3.75L12 10.5 4.75 6.75Z" stroke-linejoin="round" />
            <path d="M4.75 6.75v8.5L12 21l7.25-5.75v-8.5M12 10.5V21" stroke-linejoin="round" />
        </svg>
    @endisset

    <h2 class="text-lg font-semibold text-zinc-950 dark:text-white">{{ $title }}</h2>

    @if (filled($description))
        <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-6 flex justify-center">{{ $action }}</div>
    @endisset
</div>
