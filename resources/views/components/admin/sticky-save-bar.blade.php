@props([
    'saveAction' => 'save',
    'saveLabel' => 'Save',
    'savingLabel' => 'Saving…',
    'discardUrl' => null,
    'discardAction' => null,
    'discardLabel' => 'Discard',
    'target' => null,
    'dirtyOnly' => true,
])

@php
    $saveAction = preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\([^)]*\))?$/', (string) $saveAction) === 1
        ? (string) $saveAction
        : 'save';
    $discardAction = filled($discardAction) && preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\([^)]*\))?$/', (string) $discardAction) === 1
        ? (string) $discardAction
        : null;
    $target ??= \Illuminate\Support\Str::before($saveAction, '(');
@endphp

<div
    {{ $attributes->class('admin-sticky-save-wrap') }}
    @if ($dirtyOnly) wire:dirty @endif
>
    <div class="h-20" aria-hidden="true"></div>

    <div class="admin-sticky-save-bar" role="region" aria-label="{{ __('Unsaved changes') }}">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <div class="min-w-0">
                @isset($message)
                    {{ $message }}
                @else
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('You have unsaved changes.') }}</p>
                @endisset
            </div>

            @isset($actions)
                <div class="flex shrink-0 items-center justify-end gap-2">{{ $actions }}</div>
            @else
                <div class="flex shrink-0 items-center justify-end gap-2">
                    @if (filled($discardUrl))
                        <flux:button :href="$discardUrl" wire:navigate variant="ghost">{{ $discardLabel }}</flux:button>
                    @elseif (filled($discardAction))
                        <flux:button type="button" variant="ghost" wire:click="{{ $discardAction }}">{{ $discardLabel }}</flux:button>
                    @endif

                    <flux:button type="button" variant="primary" wire:click="{{ $saveAction }}" wire:loading.attr="disabled" wire:target="{{ $target }}">
                        <span wire:loading.remove wire:target="{{ $target }}">{{ $saveLabel }}</span>
                        <span wire:loading wire:target="{{ $target }}">{{ $savingLabel }}</span>
                    </flux:button>
                </div>
            @endisset
        </div>
    </div>
</div>
