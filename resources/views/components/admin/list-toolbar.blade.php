@props([
    'selectedCount' => 0,
])

<div {{ $attributes->class('space-y-3') }}>
    <div class="admin-list-toolbar">
        @isset($search)
            <div class="min-w-0 flex-1 sm:max-w-md">{{ $search }}</div>
        @endisset

        @isset($filters)
            <div class="flex flex-wrap items-center gap-2">{{ $filters }}</div>
        @endisset

        @isset($actions)
            <div class="flex flex-wrap items-center gap-2 sm:ml-auto">{{ $actions }}</div>
        @endisset
    </div>

    @if ((int) $selectedCount > 0)
        <div class="admin-bulk-bar" role="region" aria-label="{{ __('Bulk actions') }}">
            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ trans_choice(':count item selected|:count items selected', (int) $selectedCount, ['count' => (int) $selectedCount]) }}
            </p>
            @isset($bulk)
                <div class="flex flex-wrap items-center gap-2">{{ $bulk }}</div>
            @endisset
        </div>
    @endif
</div>
