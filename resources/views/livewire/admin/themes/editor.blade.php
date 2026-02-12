<div class="flex flex-col h-[calc(100vh-4rem)]">
    {{-- Top toolbar --}}
    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 px-4 py-3 bg-white dark:bg-zinc-900">
        <flux:button href="{{ route('admin.themes.index') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm">
            Back to themes
        </flux:button>
        <div class="flex items-center gap-2">
            <flux:text class="text-sm text-zinc-500 mr-2">{{ $theme->name }}</flux:text>
            <flux:button wire:click="save" variant="ghost" size="sm" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving...</span>
            </flux:button>
            <flux:button wire:click="publish" variant="primary" size="sm" wire:loading.attr="disabled" wire:target="publish">
                <span wire:loading.remove wire:target="publish">Save and publish</span>
                <span wire:loading wire:target="publish">Publishing...</span>
            </flux:button>
        </div>
    </div>

    {{-- Three-panel layout --}}
    <div class="flex flex-1 min-h-0">
        {{-- Left panel: Sections --}}
        <div class="w-64 shrink-0 border-r border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-y-auto">
            <div class="p-4">
                <flux:heading size="sm" class="mb-3">Sections</flux:heading>
                <div class="space-y-1">
                    @foreach ($sections as $section)
                        <button
                            wire:click="selectSection('{{ $section['key'] }}')"
                            class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors {{ $selectedSection === $section['key'] ? 'bg-zinc-100 dark:bg-zinc-800 font-semibold text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }}"
                        >
                            {{ $section['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Center panel: Preview --}}
        <div class="flex-1 bg-zinc-100 dark:bg-zinc-800 p-6 overflow-hidden">
            <div class="h-full rounded-lg border border-zinc-200 dark:border-zinc-700 shadow-lg overflow-hidden bg-white">
                <iframe src="/" class="w-full h-full" title="Theme preview"></iframe>
            </div>
        </div>

        {{-- Right panel: Settings --}}
        <div class="w-80 shrink-0 border-l border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-y-auto">
            <div class="p-4">
                @if ($selectedSection)
                    @php
                        $currentSection = collect($sections)->firstWhere('key', $selectedSection);
                    @endphp

                    @if ($currentSection)
                        <flux:heading size="sm" class="mb-1">{{ $currentSection['label'] }}</flux:heading>
                        <flux:separator class="my-4" />

                        <div class="space-y-4">
                            @foreach ($currentSection['fields'] ?? [] as $field)
                                @php
                                    $fieldValue = $sectionSettings[$selectedSection][$field['key']] ?? $field['default'] ?? '';
                                @endphp

                                <flux:field>
                                    <flux:label>{{ $field['label'] }}</flux:label>

                                    @if ($field['type'] === 'text')
                                        <flux:input
                                            value="{{ $fieldValue }}"
                                            wire:change="updateSetting('{{ $field['key'] }}', $event.target.value)"
                                        />
                                    @elseif ($field['type'] === 'textarea')
                                        <flux:textarea
                                            rows="3"
                                            wire:change="updateSetting('{{ $field['key'] }}', $event.target.value)"
                                        >{{ $fieldValue }}</flux:textarea>
                                    @elseif ($field['type'] === 'color')
                                        <input
                                            type="color"
                                            value="{{ $fieldValue }}"
                                            wire:change="updateSetting('{{ $field['key'] }}', $event.target.value)"
                                            class="h-10 w-full rounded-md border border-zinc-200 dark:border-zinc-700 cursor-pointer"
                                        />
                                    @elseif ($field['type'] === 'select')
                                        <flux:select wire:change="updateSetting('{{ $field['key'] }}', $event.target.value)">
                                            @foreach ($field['options'] ?? [] as $optVal => $optLabel)
                                                <flux:select.option value="{{ $optVal }}" :selected="$fieldValue == $optVal">{{ $optLabel }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    @elseif ($field['type'] === 'checkbox')
                                        <flux:checkbox
                                            :checked="(bool) $fieldValue"
                                            wire:change="updateSetting('{{ $field['key'] }}', $event.target.checked)"
                                            label="{{ $field['label'] }}"
                                        />
                                    @endif
                                </flux:field>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="flex items-center justify-center h-64 text-zinc-500 dark:text-zinc-400 text-sm">
                        Select a section to edit its settings.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
