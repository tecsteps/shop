@props(['heading' => null])

{{-- Reusable admin card (spec 03 section 19.7). --}}
<div {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900']) }}>
    @if ($heading !== null)
        <flux:heading>{{ $heading }}</flux:heading>
        <flux:separator class="my-4" />
    @endif

    {{ $slot }}
</div>
