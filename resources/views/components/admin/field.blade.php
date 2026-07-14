@props([
    'name',
    'label',
    'for' => null,
    'description' => null,
    'error' => null,
    'required' => false,
])

@php
    $errors ??= new \Illuminate\Support\ViewErrorBag();
    $error ??= $errors->first((string) $name);
    $fieldId = $for ?: str_replace(['.', '[', ']'], '-', (string) $name);
    $descriptionId = $fieldId.'-description';
    $errorId = $fieldId.'-error';
@endphp

<flux:field {{ $attributes }}>
    <flux:label :for="$fieldId">
        {{ $label }}
        @if ($required)<span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>@endif
    </flux:label>

    @if (filled($description))
        <flux:description :id="$descriptionId">{{ $description }}</flux:description>
    @endif

    {{ $slot }}

    @if (filled($error))
        <p id="{{ $errorId }}" class="mt-2 text-sm font-medium text-red-600 dark:text-red-400" role="alert">{{ $error }}</p>
    @endif
</flux:field>
