<div>
    {{-- Top toolbar --}}
    <div class="flex flex-wrap items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.themes.index')" wire:navigate>
            Back to themes
        </flux:button>

        <flux:spacer />

        <flux:button variant="ghost" wire:click="save" wire:loading.attr="disabled">Save</flux:button>
        <flux:button variant="primary" wire:click="publish" wire:loading.attr="disabled">Save &amp; publish</flux:button>
    </div>

    {{-- Three-panel layout --}}
    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-[264px_1fr_320px]">
        {{-- Sections panel --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="sm" class="mb-3">Sections</flux:heading>

            <div class="space-y-1">
                @foreach ($sections as $section)
                    <button
                        type="button"
                        wire:click="selectSection('{{ $section['key'] }}')"
                        @class([
                            'w-full rounded-lg px-3 py-2 text-start text-sm transition',
                            'bg-zinc-900 font-semibold text-white dark:bg-white dark:text-zinc-900' => $selectedSection === $section['key'],
                            'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => $selectedSection !== $section['key'],
                        ])
                    >
                        {{ $section['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Preview panel --}}
        <div class="min-h-[60vh] rounded-xl bg-zinc-100 p-4 dark:bg-zinc-800">
            <div class="flex h-full items-start justify-between gap-3">
                <flux:text>Live preview</flux:text>
                <flux:button variant="ghost" size="sm" icon="arrow-path" wire:click="refreshPreview">
                    Refresh
                </flux:button>
            </div>

            <iframe
                id="theme-preview"
                src="{{ $previewUrl }}"
                x-on:refresh-preview.window="$el.contentWindow.location.reload()"
                class="mt-3 h-[calc(100%-3rem)] w-full rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700"
                title="Storefront preview"
            ></iframe>
        </div>

        {{-- Settings panel --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            @php
                $section = collect($sections)->firstWhere('key', $selectedSection);
            @endphp

            <flux:heading size="sm">{{ $section['label'] ?? 'Settings' }} settings</flux:heading>

            <flux:separator class="my-4" />

            @if ($selectedSection === null)
                <flux:text>Select a section to edit its settings.</flux:text>
            @else
                <div class="space-y-4">
                    @foreach ($this->selectedFields as $field)
                        @switch($field['type'])
                            @case('textarea')
                                <flux:field>
                                    <flux:label>{{ $field['label'] }}</flux:label>
                                    <flux:textarea
                                        rows="3"
                                        wire:model.live.debounce.500ms="sectionSettings.{{ $field['key'] }}"
                                    />
                                </flux:field>
                                @break

                            @case('color')
                                <flux:field>
                                    <flux:label>{{ $field['label'] }}</flux:label>
                                    <input
                                        type="color"
                                        wire:change="updateSetting('{{ $field['key'] }}', $event.target.value)"
                                        value="{{ $sectionSettings[$field['key']] ?? '#000000' }}"
                                        class="h-10 w-full cursor-pointer rounded-lg border border-zinc-200 bg-white dark:border-zinc-700"
                                    />
                                </flux:field>
                                @break

                            @case('checkbox')
                                <flux:checkbox
                                    wire:model="sectionSettings.{{ $field['key'] }}"
                                    :label="$field['label']"
                                />
                                @break

                            @case('select')
                                <flux:field>
                                    <flux:label>{{ $field['label'] }}</flux:label>
                                    <flux:select wire:model.live="sectionSettings.{{ $field['key'] }}">
                                        @foreach ($field['options'] ?? [] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                                @break

                            @default
                                <flux:field>
                                    <flux:label>{{ $field['label'] }}</flux:label>
                                    <flux:input
                                        wire:model.live.debounce.500ms="sectionSettings.{{ $field['key'] }}"
                                        placeholder="{{ $field['label'] }}"
                                    />
                                </flux:field>
                        @endswitch
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
