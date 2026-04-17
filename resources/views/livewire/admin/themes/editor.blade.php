<div class="-mx-4 -my-6 sm:-mx-6 lg:-mx-8">
    {{-- Toolbar --}}
    <div class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
        <flux:button variant="ghost" href="{{ route('admin.themes.index') }}" wire:navigate icon="arrow-left">
            Back to themes
        </flux:button>
        <div class="flex gap-2">
            <flux:button variant="ghost" wire:click="save">Save</flux:button>
            <flux:button variant="primary" wire:click="publish">Save and publish</flux:button>
        </div>
    </div>

    <div class="flex" style="height: calc(100vh - 128px);">
        {{-- Left Panel: Sections --}}
        <div class="w-64 shrink-0 overflow-y-auto border-r border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <flux:heading size="md" class="mb-3">Sections</flux:heading>
            @foreach ($sections as $key => $section)
                <button
                    wire:click="selectSection('{{ $key }}')"
                    @class([
                        'w-full rounded-lg px-3 py-2 text-left text-sm transition-colors',
                        'bg-gray-100 font-medium text-gray-900 dark:bg-gray-800 dark:text-white' => $selectedSection === $key,
                        'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800' => $selectedSection !== $key,
                    ])
                    wire:key="section-{{ $key }}"
                >
                    {{ $section['label'] ?? ucfirst($key) }}
                </button>
            @endforeach
        </div>

        {{-- Center Panel: Preview --}}
        <div class="flex-1 bg-gray-200 p-8 dark:bg-gray-800">
            <iframe src="{{ $previewUrl }}" class="h-full w-full rounded-lg border border-gray-300 bg-white shadow-lg dark:border-gray-600"></iframe>
        </div>

        {{-- Right Panel: Settings --}}
        <div class="w-80 shrink-0 overflow-y-auto border-l border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            @if ($selectedSection && isset($sections[$selectedSection]))
                <flux:heading size="md" class="mb-1">{{ $sections[$selectedSection]['label'] ?? ucfirst($selectedSection) }}</flux:heading>
                <flux:separator class="my-3" />

                <div class="space-y-4">
                    @foreach ($sections[$selectedSection]['fields'] ?? [] as $fieldKey => $field)
                        @if (($field['type'] ?? 'text') === 'text')
                            <flux:field>
                                <flux:label>{{ $field['label'] ?? $fieldKey }}</flux:label>
                                <flux:input wire:model.live.debounce.500ms="sectionSettings.{{ $fieldKey }}" />
                            </flux:field>
                        @elseif ($field['type'] === 'textarea')
                            <flux:field>
                                <flux:label>{{ $field['label'] ?? $fieldKey }}</flux:label>
                                <flux:textarea wire:model.live.debounce.500ms="sectionSettings.{{ $fieldKey }}" rows="3" />
                            </flux:field>
                        @elseif ($field['type'] === 'color')
                            <flux:field>
                                <flux:label>{{ $field['label'] ?? $fieldKey }}</flux:label>
                                <input type="color" wire:model.live="sectionSettings.{{ $fieldKey }}" class="h-10 w-full rounded border border-gray-200 dark:border-gray-700" />
                            </flux:field>
                        @elseif ($field['type'] === 'select')
                            <flux:field>
                                <flux:label>{{ $field['label'] ?? $fieldKey }}</flux:label>
                                <flux:select wire:model.live="sectionSettings.{{ $fieldKey }}">
                                    @foreach ($field['options'] ?? [] as $optValue => $optLabel)
                                        <option value="{{ $optValue }}">{{ $optLabel }}</option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                        @elseif ($field['type'] === 'checkbox')
                            <flux:checkbox wire:model.live="sectionSettings.{{ $fieldKey }}" label="{{ $field['label'] ?? $fieldKey }}" />
                        @endif
                    @endforeach
                </div>
            @else
                <div class="flex h-full items-center justify-center">
                    <flux:text class="text-gray-500">Select a section to edit its settings.</flux:text>
                </div>
            @endif
        </div>
    </div>
</div>
