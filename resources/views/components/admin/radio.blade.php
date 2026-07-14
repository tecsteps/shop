@props([
    'label',
    'description' => null,
    'checked' => false,
    'card' => false,
])

<label @class([
    'flex cursor-pointer items-start gap-3',
    'rounded-xl border p-4 transition-colors has-checked:border-blue-600 has-checked:bg-blue-50/50 dark:border-zinc-700 dark:has-checked:border-blue-500 dark:has-checked:bg-blue-950/30' => $card,
])>
    <input
        type="radio"
        @checked($checked)
        {{ $attributes->class('mt-0.5 size-4 shrink-0 accent-blue-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600') }}
    >
    <span class="min-w-0">
        <span class="block text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $label }}</span>

        @if (filled($description))
            <span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</span>
        @endif
    </span>
</label>
