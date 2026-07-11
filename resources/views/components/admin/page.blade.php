@props(['title', 'subtitle' => null])
<div class="space-y-6">
    <flux:breadcrumbs><flux:breadcrumbs.item href="/admin" wire:navigate>Home</flux:breadcrumbs.item><flux:breadcrumbs.item>{{ $title }}</flux:breadcrumbs.item></flux:breadcrumbs>
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div class="space-y-1"><flux:heading size="xl" level="1">{{ $title }}</flux:heading>@if ($subtitle)<flux:text>{{ $subtitle }}</flux:text>@endif</div>
        @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
    </div>
    {{ $slot }}
</div>
