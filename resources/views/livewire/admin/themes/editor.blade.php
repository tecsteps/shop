<div class="flex min-h-dvh flex-col bg-zinc-100 dark:bg-zinc-950">
    <header class="z-20 flex min-h-16 flex-wrap items-center justify-between gap-3 border-b border-zinc-200 bg-white px-3 py-2 shadow-sm sm:px-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('admin.themes') }}" wire:navigate class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl px-3 text-sm font-medium text-zinc-700 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-zinc-200 dark:hover:bg-zinc-800">
                <flux:icon name="arrow-left" class="size-4" />
                <span class="hidden sm:inline">Back to themes</span>
                <span class="sm:hidden">Back</span>
            </a>
            <span class="hidden h-8 w-px bg-zinc-200 sm:block dark:bg-zinc-700" aria-hidden="true"></span>
            <div class="min-w-0">
                <h1 class="truncate text-sm font-semibold sm:text-base">{{ $theme->name }}</h1>
                <p class="hidden text-xs text-zinc-500 sm:block dark:text-zinc-400">Theme editor</p>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <button type="button" wire:click="refreshPreview" class="inline-flex min-h-10 items-center gap-2 rounded-xl px-3 text-sm font-medium text-zinc-600 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
                <flux:icon name="arrow-path" class="size-4" />
                <span class="hidden sm:inline">Refresh preview</span>
                <span class="sm:hidden">Refresh</span>
            </button>
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-zinc-300 bg-white px-3 text-sm font-semibold text-zinc-800 hover:bg-zinc-50 focus-visible:outline-2 focus-visible:outline-blue-600 disabled:opacity-60 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
            <button type="button" wire:click="saveAndPublish" wire:loading.attr="disabled" wire:target="saveAndPublish" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-blue-700 px-3 text-sm font-semibold text-white hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-60 sm:px-4">
                <span class="hidden sm:inline">Save and publish</span>
                <span class="sm:hidden">Publish</span>
            </button>
        </div>
    </header>

    @if (session('status'))
        <div class="border-b border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100" role="status" aria-live="polite">{{ session('status') }}</div>
    @endif

    <main class="grid flex-1 lg:min-h-0 lg:grid-cols-[16.5rem_minmax(0,1fr)_20rem]" aria-label="Theme customization workspace">
        <aside class="border-b border-zinc-200 bg-white p-4 sm:p-5 lg:min-h-0 lg:overflow-y-auto lg:border-b-0 lg:border-r dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="theme-sections-heading">
            <h2 id="theme-sections-heading" class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Sections</h2>
            @if ($sections === [])
                <p class="mt-4 rounded-xl bg-zinc-50 p-4 text-sm text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">This theme has no editable settings.</p>
            @else
                <nav class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-1" aria-label="Theme sections">
                    @foreach ($sections as $section)
                        <button type="button" wire:click="selectSection('{{ $section['key'] }}')" wire:key="theme-section-{{ $section['key'] }}" @class([
                            'flex min-h-11 items-center justify-between gap-2 rounded-xl px-3 text-left text-sm transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600',
                            'bg-blue-50 font-semibold text-blue-900 dark:bg-blue-950/50 dark:text-blue-100' => $selectedSection === $section['key'],
                            'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => $selectedSection !== $section['key'],
                        ])>
                            <span class="truncate">{{ $section['label'] }}</span>
                            @if ($selectedSection === $section['key'])
                                <flux:icon name="chevron-right" class="size-4 shrink-0" />
                            @endif
                        </button>
                    @endforeach
                </nav>
            @endif
        </aside>

        <section class="order-last flex min-h-[26rem] min-w-0 flex-col bg-zinc-100 p-3 sm:p-5 lg:order-none lg:min-h-0 lg:p-6 dark:bg-zinc-950" aria-label="Storefront preview">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">Live preview</h2>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">Storefront</span>
            </div>
            <div class="min-h-[24rem] flex-1 overflow-hidden rounded-2xl border border-zinc-300 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900 lg:min-h-0">
                <iframe wire:key="theme-preview-{{ $previewVersion }}" src="{{ $previewUrl }}" title="Live preview of the store storefront" class="size-full min-h-[24rem] bg-white lg:min-h-0" loading="lazy"></iframe>
            </div>
        </section>

        <aside class="border-t border-zinc-200 bg-white p-4 sm:p-5 lg:min-h-0 lg:overflow-y-auto lg:border-l lg:border-t-0 dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="theme-settings-heading">
            @if ($selectedSection === null)
                <div class="flex min-h-48 items-center justify-center rounded-xl bg-zinc-50 p-6 text-center dark:bg-zinc-950">
                    <p class="max-w-xs text-sm text-zinc-500 dark:text-zinc-400">Select a section to edit its settings.</p>
                </div>
            @else
                @php($activeSection = collect($sections)->firstWhere('key', $selectedSection))
                <h2 id="theme-settings-heading" class="text-base font-semibold">{{ $activeSection['label'] ?? 'Section settings' }}</h2>
                <flux:separator class="my-4" />

                @if (($activeSection['fields'] ?? []) === [])
                    <p class="rounded-xl bg-zinc-50 p-4 text-sm text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">This section has no settings.</p>
                @else
                    <div class="grid gap-5">
                        @foreach ($activeSection['fields'] as $field)
                            @php($fieldId = 'theme-setting-'.$theme->id.'-'.$field['key'])
                            <div wire:key="theme-setting-{{ $field['key'] }}">
                                @if ($field['type'] === 'checkbox')
                                    <label for="{{ $fieldId }}" class="flex min-h-11 cursor-pointer items-center gap-3 text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                        <input id="{{ $fieldId }}" type="checkbox" wire:model.live="sectionSettings.{{ $field['key'] }}" class="size-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-600 dark:border-zinc-600 dark:bg-zinc-950">
                                        <span>{{ $field['label'] }}</span>
                                    </label>
                                @else
                                    <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $field['label'] }}</label>
                                    @if ($field['type'] === 'textarea')
                                        <textarea id="{{ $fieldId }}" rows="3" wire:model.live.debounce.500ms="sectionSettings.{{ $field['key'] }}" @required($field['required']) class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">{{ $sectionSettings[$field['key']] ?? '' }}</textarea>
                                    @elseif ($field['type'] === 'select')
                                        <select id="{{ $fieldId }}" wire:model.live="sectionSettings.{{ $field['key'] }}" @required($field['required']) class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3 text-sm shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                                            @foreach ($field['options'] as $option)
                                                <option value="{{ $option }}">{{ \Illuminate\Support\Str::headline((string) $option) }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <div class="flex items-center gap-3">
                                            <input id="{{ $fieldId }}" type="{{ $field['type'] === 'color' ? 'color' : ($field['type'] === 'number' ? 'number' : 'text') }}" wire:model.live.debounce.500ms="sectionSettings.{{ $field['key'] }}" @required($field['required']) @class([
                                                'min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3 text-sm shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950',
                                                'h-12 max-w-16 cursor-pointer p-1' => $field['type'] === 'color',
                                            ])>
                                            @if ($field['type'] === 'color')
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $sectionSettings[$field['key']] ?? '#000000' }}</span>
                                            @endif
                                        </div>
                                    @endif
                                @endif
                                @error('settings.'.$field['path'])
                                    <p class="mt-1.5 text-sm text-rose-700 dark:text-rose-300" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </aside>
    </main>
</div>
