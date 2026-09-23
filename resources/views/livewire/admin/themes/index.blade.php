<div>
    @include('livewire.admin.components.page-heading', [
        'title' => 'Themes',
        'description' => 'Choose and customize the look of your online store.',
    ])

    @if ($themes->isEmpty())
        <section class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900" aria-labelledby="themes-empty-heading">
            <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                <flux:icon name="paint-brush" class="size-7" />
            </span>
            <h2 id="themes-empty-heading" class="mt-4 text-lg font-semibold">No themes installed</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-zinc-500 dark:text-zinc-400">Install a theme to customize your storefront appearance.</p>
        </section>
    @else
        <section class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3" aria-label="Available themes">
            @foreach ($themes as $theme)
                <article wire:key="admin-theme-{{ $theme->id }}" @class([
                    'overflow-hidden rounded-2xl border bg-white shadow-sm transition dark:bg-zinc-900',
                    'border-blue-400 ring-2 ring-blue-500/30 dark:border-blue-700' => $theme->is_active,
                    'border-zinc-200 dark:border-zinc-800' => ! $theme->is_active,
                ])>
                    <div class="relative flex aspect-video items-center justify-center overflow-hidden bg-gradient-to-br from-zinc-100 via-white to-blue-50 dark:from-zinc-800 dark:via-zinc-900 dark:to-blue-950/50" role="img" aria-label="Preview placeholder for {{ $theme->name }}">
                        <span class="flex size-16 items-center justify-center rounded-2xl border border-white/70 bg-white/80 text-blue-600 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/80 dark:text-blue-300">
                            <flux:icon name="paint-brush" class="size-8" />
                        </span>
                        @if ($theme->is_active)
                            <span class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm">
                                <span class="size-1.5 rounded-full bg-white" aria-hidden="true"></span>
                                Published theme
                            </span>
                        @endif
                    </div>

                    <div class="grid gap-5 p-5">
                        <div class="flex min-w-0 items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="truncate text-base font-semibold text-zinc-950 dark:text-white">{{ $theme->name }}</h2>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $theme->version ? 'v'.$theme->version : 'No version' }}</span>
                                </div>
                                <div class="mt-2">
                                    @include('livewire.admin.components.status-badge', ['status' => $theme->status])
                                </div>
                            </div>

                            <details class="relative shrink-0">
                                <summary class="flex size-10 cursor-pointer list-none items-center justify-center rounded-xl text-zinc-500 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="More actions for {{ $theme->name }}">
                                    <flux:icon name="ellipsis-horizontal" class="size-5" />
                                </summary>
                                <div class="absolute right-0 z-20 mt-2 w-48 rounded-xl border border-zinc-200 bg-white p-1.5 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                                    <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                        <flux:icon name="eye" class="size-4" /> Preview store<span class="sr-only"> (opens in a new tab)</span>
                                    </a>
                                    @unless ($theme->is_active)
                                        <button type="button" wire:click="publishTheme({{ $theme->id }})" wire:confirm="Publish {{ $theme->name }} as your live theme?" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-left text-sm text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                            <flux:icon name="check-circle" class="size-4" /> Publish
                                        </button>
                                    @endunless
                                    <button type="button" wire:click="duplicateTheme({{ $theme->id }})" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-left text-sm text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                        <flux:icon name="document-duplicate" class="size-4" /> Duplicate
                                    </button>
                                    <div class="my-1 border-t border-zinc-200 dark:border-zinc-700" role="separator"></div>
                                    <button type="button" wire:click="deleteTheme({{ $theme->id }})" wire:confirm="Delete {{ $theme->name }}? This cannot be undone." class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-left text-sm text-rose-700 hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-rose-600 dark:text-rose-300 dark:hover:bg-rose-950/40">
                                        <flux:icon name="trash" class="size-4" /> Delete
                                    </button>
                                </div>
                            </details>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            <a href="{{ route('admin.themes.editor', ['theme' => $theme->id]) }}" wire:navigate class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                <flux:icon name="pencil-square" class="size-4" /> Customize
                            </a>
                            @if (data_get($theme->settings?->settings_json ?? [], 'colors.primary'))
                                <span class="inline-flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                    <span class="size-5 rounded-md border border-zinc-300 dark:border-zinc-600" style="background-color: {{ data_get($theme->settings?->settings_json ?? [], 'colors.primary') }}" aria-hidden="true"></span>
                                    Primary color
                                </span>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </section>
    @endif
</div>
