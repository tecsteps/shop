@props([
    'label',
    'description' => null,
    'checked' => false,
])

<label class="flex cursor-pointer items-start justify-between gap-4">
    <span class="min-w-0">
        <span class="block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $label }}</span>

        @if (filled($description))
            <span class="mt-1 block text-sm leading-5 text-zinc-500 dark:text-zinc-400">{{ $description }}</span>
        @endif
    </span>

    <span class="relative mt-0.5 inline-flex shrink-0">
        <input
            type="checkbox"
            role="switch"
            @checked($checked)
            {{ $attributes->class('peer sr-only') }}
        >
        <span
            aria-hidden="true"
            class="h-5 w-8 rounded-full bg-zinc-300 transition-colors after:absolute after:left-[0.1875rem] after:top-[0.1875rem] after:size-3.5 after:rounded-full after:bg-white after:shadow-sm after:transition-transform peer-checked:bg-blue-600 peer-checked:after:translate-x-3 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-blue-600 peer-disabled:cursor-not-allowed peer-disabled:opacity-50 dark:bg-zinc-700 dark:peer-checked:bg-blue-500"
        ></span>
    </span>
</label>
