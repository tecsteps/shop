@props(['title' => null])

{{-- Reusable admin card wrapper (spec 19.7). --}}
<div {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900']) }}>
    @if ($title)
        <flux:heading size="md" class="mb-4">{{ $title }}</flux:heading>
    @endif

    {{ $slot }}
</div>
