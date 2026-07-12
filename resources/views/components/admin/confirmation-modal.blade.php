@props([
    'name',
    'title',
    'description' => null,
    'confirmAction' => null,
    'confirmLabel' => 'Confirm',
    'confirmingLabel' => 'Working…',
    'cancelLabel' => 'Cancel',
    'target' => null,
    'show' => false,
])

@php
    $name = preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', (string) $name) === 1 ? (string) $name : 'confirmation-modal';
    $confirmAction = filled($confirmAction) && preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\([^)]*\))?$/', (string) $confirmAction) === 1
        ? (string) $confirmAction
        : null;
    if (blank($target) && filled($confirmAction)) {
        $target = \Illuminate\Support\Str::before($confirmAction, '(');
    }
@endphp

@isset($trigger)
    <flux:modal.trigger :name="$name">{{ $trigger }}</flux:modal.trigger>
@endisset

<flux:modal :name="$name" :show="$show" focusable class="max-w-md">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $title }}</flux:heading>

            @if (filled($description))
                <flux:text class="mt-2 leading-6">{{ $description }}</flux:text>
            @endif

            @if (! $slot->isEmpty())
                <div class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $slot }}</div>
            @endif
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <flux:modal.close>
                <flux:button type="button" variant="ghost">{{ $cancelLabel }}</flux:button>
            </flux:modal.close>

            @if (filled($confirmAction))
                <flux:button type="button" variant="danger" wire:click="{{ $confirmAction }}" wire:loading.attr="disabled" wire:target="{{ $target }}">
                    <span wire:loading.remove wire:target="{{ $target }}">{{ $confirmLabel }}</span>
                    <span wire:loading wire:target="{{ $target }}">{{ $confirmingLabel }}</span>
                </flux:button>
            @else
                <flux:button type="submit" variant="danger">{{ $confirmLabel }}</flux:button>
            @endif
        </div>
    </div>
</flux:modal>
