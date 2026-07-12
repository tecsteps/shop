@props([
    'items',
    'active',
    'wireModel' => null,
    'label' => 'Filters',
])

@php
    $active = $active instanceof \BackedEnum ? $active->value : (string) $active;
    $wireModel = filled($wireModel) && preg_match('/^[a-zA-Z][a-zA-Z0-9_.]*$/', (string) $wireModel) === 1
        ? (string) $wireModel
        : null;
@endphp

<div {{ $attributes->class('admin-tabs') }} role="tablist" aria-label="{{ $label }}">
    @foreach ($items as $item)
        @php
            $value = data_get($item, 'value', data_get($item, 'label', ''));
            $value = $value instanceof \BackedEnum ? $value->value : (string) $value;
            $itemLabel = (string) data_get($item, 'label', \Illuminate\Support\Str::headline($value));
            $url = data_get($item, 'url');
            $count = data_get($item, 'count');
            $disabled = (bool) data_get($item, 'disabled', false);
            $isActive = $active === $value;
            $setAction = $wireModel
                ? '$set('.\Illuminate\Support\Js::from($wireModel).', '.\Illuminate\Support\Js::from($value).')'
                : null;
        @endphp

        @if (filled($url))
            <a href="{{ $url }}" wire:navigate class="admin-tab" role="tab" aria-selected="{{ $isActive ? 'true' : 'false' }}" @if ($isActive) aria-current="page" @endif>
                <span>{{ $itemLabel }}</span>
                @if ($count !== null)<span class="admin-tab-count">{{ $count }}</span>@endif
            </a>
        @else
            <button
                type="button"
                class="admin-tab"
                role="tab"
                aria-selected="{{ $isActive ? 'true' : 'false' }}"
                @if ($setAction) wire:click="{{ $setAction }}" wire:loading.attr="disabled" wire:target="{{ $wireModel }}" @endif
                @disabled($disabled)
            >
                <span>{{ $itemLabel }}</span>
                @if ($count !== null)<span class="admin-tab-count">{{ $count }}</span>@endif
            </button>
        @endif
    @endforeach
</div>
