@if ($themeSettings['show_announcement_bar'] && filled($themeSettings['announcement_text']))
    <div
        x-data="{
            dismissed: localStorage.getItem('sf-announcement-dismissed') === @js(md5($themeSettings['announcement_text'])),
            dismiss() {
                this.dismissed = true;
                localStorage.setItem('sf-announcement-dismissed', @js(md5($themeSettings['announcement_text'])));
            },
        }"
        x-show="! dismissed"
        x-cloak
        class="bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900"
    >
        <div class="mx-auto flex max-w-7xl items-center justify-center gap-3 px-4 py-2 sm:px-6 lg:px-8">
            <p class="text-center text-xs font-medium sm:text-sm">
                @if (filled($themeSettings['announcement_link']))
                    <a href="{{ $themeSettings['announcement_link'] }}" class="underline underline-offset-2 hover:no-underline">
                        {{ $themeSettings['announcement_text'] }}
                    </a>
                @else
                    {{ $themeSettings['announcement_text'] }}
                @endif
            </p>
            <button
                type="button"
                x-on:click="dismiss()"
                class="shrink-0 rounded p-1 transition hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-white dark:hover:bg-zinc-900/10 dark:focus-visible:ring-zinc-900"
                aria-label="{{ __('Dismiss announcement') }}"
            >
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
@endif
