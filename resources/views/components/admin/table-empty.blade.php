@props([
    'colspan' => 1,
    'title' => 'No results',
    'description' => null,
])

<tr>
    <td colspan="{{ max(1, (int) $colspan) }}" class="px-6 py-14 text-center">
        <div class="mx-auto flex max-w-md flex-col items-center">
            @isset($icon)
                <div class="mb-4 text-zinc-300 dark:text-zinc-700">{{ $icon }}</div>
            @else
                <svg aria-hidden="true" class="mb-4 size-10 text-zinc-300 dark:text-zinc-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4">
                    <path d="M4 7.5h16M7.5 4v7M16.5 4v7M5.5 20h13a1.5 1.5 0 0 0 1.5-1.5v-13A1.5 1.5 0 0 0 18.5 4h-13A1.5 1.5 0 0 0 4 5.5v13A1.5 1.5 0 0 0 5.5 20Z" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            @endisset

            <p class="font-semibold text-zinc-950 dark:text-white">{{ $title }}</p>

            @if (filled($description))
                <p class="mt-1.5 text-sm leading-6 text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
            @endif

            @isset($action)
                <div class="mt-5">{{ $action }}</div>
            @endisset
        </div>
    </td>
</tr>
