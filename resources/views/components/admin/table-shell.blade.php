@props([
    'caption' => null,
    'loadingTarget' => null,
    'loadingLabel' => 'Loading table',
])

<div {{ $attributes->class('admin-table-shell') }}>
    <div class="relative overflow-x-auto">
        <table class="admin-table">
            @if (filled($caption))
                <caption class="sr-only">{{ $caption }}</caption>
            @endif

            @isset($head)
                <thead>{{ $head }}</thead>
            @endisset

            <tbody
                class="divide-y divide-zinc-200 transition-opacity dark:divide-zinc-800"
                @if (filled($loadingTarget)) wire:loading.class="opacity-40" wire:target="{{ $loadingTarget }}" @endif
            >
                {{ $slot }}
            </tbody>
        </table>

        @if (filled($loadingTarget))
            <x-admin.loading-overlay :target="$loadingTarget" :label="$loadingLabel" />
        @endif
    </div>

    @isset($pagination)
        <footer class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800 sm:px-6">
            {{ $pagination }}
        </footer>
    @endisset
</div>
