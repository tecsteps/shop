<div>
    {{-- Top toolbar --}}
    <div class="flex items-center justify-between mb-6 pb-4 border-b border-zinc-200 dark:border-zinc-700">
        <flux:button variant="ghost" :href="route('admin.themes.index')" wire:navigate>
            <flux:icon name="arrow-left" class="size-4 mr-1" /> Back to themes
        </flux:button>
        <div class="flex items-center gap-3">
            <flux:button variant="ghost" wire:click="save">Save</flux:button>
            <flux:button variant="primary" wire:click="publish">Save and publish</flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 min-h-[600px]">
        {{-- Left panel: sections --}}
        <div class="lg:col-span-3">
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                <flux:heading size="md" class="mb-3">Sections</flux:heading>
                <div class="space-y-1">
                    @foreach ($sections as $section)
                        <button
                            wire:click="selectSection('{{ $section['key'] }}')"
                            class="w-full text-left px-3 py-2 rounded text-sm transition-colors {{ $selectedSection === $section['key'] ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                        >
                            {{ $section['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Center: preview --}}
        <div class="lg:col-span-6">
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-800 h-full min-h-[500px]">
                <iframe
                    src="{{ $previewUrl }}"
                    class="w-full h-full min-h-[500px]"
                    title="Theme preview"
                ></iframe>
            </div>
        </div>

        {{-- Right panel: settings --}}
        <div class="lg:col-span-3">
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                @if ($selectedSection)
                    @php
                        $currentSection = collect($sections)->firstWhere('key', $selectedSection);
                    @endphp

                    @if ($currentSection)
                        <flux:heading size="md" class="mb-1">{{ $currentSection['label'] }}</flux:heading>
                        <flux:separator class="my-3" />

                        <div class="space-y-4">
                            @foreach ($currentSection['fields'] ?? [] as $field)
                                @if ($field['type'] === 'text')
                                    <flux:input
                                        wire:model.live.debounce.500ms="sectionSettings.{{ $field['key'] }}"
                                        label="{{ $field['label'] }}"
                                    />
                                @elseif ($field['type'] === 'textarea')
                                    <flux:textarea
                                        wire:model.live.debounce.500ms="sectionSettings.{{ $field['key'] }}"
                                        label="{{ $field['label'] }}"
                                        rows="3"
                                    />
                                @elseif ($field['type'] === 'color')
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ $field['label'] }}</label>
                                        <input
                                            type="color"
                                            wire:model.live="sectionSettings.{{ $field['key'] }}"
                                            class="w-full h-10 rounded border border-zinc-200 dark:border-zinc-700 cursor-pointer"
                                        />
                                    </div>
                                @elseif ($field['type'] === 'checkbox')
                                    <flux:checkbox
                                        wire:model.live="sectionSettings.{{ $field['key'] }}"
                                        label="{{ $field['label'] }}"
                                    />
                                @elseif ($field['type'] === 'select')
                                    <flux:select
                                        wire:model.live="sectionSettings.{{ $field['key'] }}"
                                        label="{{ $field['label'] }}"
                                    >
                                        @foreach ($field['options'] ?? [] as $optValue => $optLabel)
                                            <option value="{{ $optValue }}">{{ $optLabel }}</option>
                                        @endforeach
                                    </flux:select>
                                @endif
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="text-center py-8">
                        <flux:text>Select a section to edit its settings.</flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
