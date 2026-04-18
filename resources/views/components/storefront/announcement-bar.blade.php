@php
    $settings = app('current_store')->settings?->settings_json ?? [];
    $text = $settings['announcement_bar'] ?? null;
@endphp
@if ($text)
    <div class="bg-zinc-900 py-2 text-center text-sm text-white dark:bg-zinc-800">
        {{ $text }}
    </div>
@endif
