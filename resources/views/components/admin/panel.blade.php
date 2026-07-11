@props(['heading' => null, 'description' => null])
<section {{ $attributes->class(['rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900']) }}>
    @if ($heading)<div class="mb-4 space-y-1"><flux:heading size="lg">{{ $heading }}</flux:heading>@if ($description)<flux:text>{{ $description }}</flux:text>@endif</div>@endif
    {{ $slot }}
</section>
