@php
    $show = $settings['show_announcement_bar'] ?? false;
    $text = $settings['announcement_text'] ?? '';
    $link = $settings['announcement_link'] ?? null;
    $bgColor = $settings['announcement_bg_color'] ?? '#111827';
@endphp

@if ($show && $text !== '')
    <div
        x-data="{ dismissed: localStorage.getItem('announcement-dismissed') === '1' }"
        x-show="! dismissed"
        x-cloak
        style="background-color: {{ $bgColor }}"
        class="relative z-40"
    >
        <div class="mx-auto flex max-w-7xl items-center justify-center gap-4 px-4 py-2.5 sm:px-6 lg:px-8">
            <p class="text-center text-xs font-medium text-white/90 sm:text-sm">
                @if ($link)
                    <a href="{{ $link }}" class="underline underline-offset-2 hover:text-white">{{ $text }}</a>
                @else
                    {{ $text }}
                @endif
            </p>
            <button
                type="button"
                @click="dismissed = true; localStorage.setItem('announcement-dismissed', '1')"
                aria-label="Dismiss announcement"
                class="shrink-0 rounded p-1 text-white/70 transition hover:text-white"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
@endif
