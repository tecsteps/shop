@php
    $sections = $this->sections;
    $current = $selectedSection ? ($sections[$selectedSection] ?? null) : null;
@endphp

<div class="-m-6 flex h-[calc(100vh-4rem)] flex-col lg:-m-8">
    {{-- Toolbar. --}}
    <div class="flex items-center justify-between border-b border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('admin.themes.index')" wire:navigate>{{ __('Back to themes') }}</flux:button>
        <div class="flex gap-2">
            <flux:button size="sm" variant="ghost" wire:click="save" data-test="save-theme">{{ __('Save') }}</flux:button>
            <flux:button size="sm" variant="primary" wire:click="publish">{{ __('Save and publish') }}</flux:button>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden">
        {{-- Sections panel. --}}
        <div class="w-64 shrink-0 overflow-y-auto border-e border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="sm" class="mb-3">{{ __('Sections') }}</flux:heading>
            <nav class="space-y-1">
                @foreach ($sections as $key => $section)
                    <button
                        type="button"
                        wire:click="selectSection('{{ $key }}')"
                        @class([
                            'w-full rounded-lg px-3 py-2 text-left text-sm transition',
                            'bg-zinc-100 font-semibold text-zinc-900 dark:bg-zinc-800 dark:text-white' => $selectedSection === $key,
                            'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50' => $selectedSection !== $key,
                        ])
                        data-test="theme-section-{{ $key }}"
                    >{{ $section['label'] }}</button>
                @endforeach
            </nav>
        </div>

        {{-- Live preview. --}}
        <div class="flex-1 overflow-hidden bg-zinc-100 p-4 dark:bg-zinc-950">
            <iframe src="{{ $this->previewUrl }}" class="size-full rounded-lg border border-zinc-200 bg-white shadow dark:border-zinc-700" title="{{ __('Theme preview') }}"></iframe>
        </div>

        {{-- Settings panel. --}}
        <div class="w-80 shrink-0 overflow-y-auto border-s border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            @if ($current)
                <flux:heading size="md">{{ $current['label'] }}</flux:heading>
                <flux:separator class="my-3" />
                <div class="space-y-4">
                    @foreach ($current['fields'] as $field)
                        @switch($field['type'])
                            @case('text')
                                <flux:field>
                                    <flux:label>{{ $field['label'] }}</flux:label>
                                    <flux:input wire:model.live.debounce.500ms="settings.{{ $field['key'] }}" />
                                </flux:field>
                                @break
                            @case('textarea')
                                <flux:field>
                                    <flux:label>{{ $field['label'] }}</flux:label>
                                    <flux:textarea rows="3" wire:model.live.debounce.500ms="settings.{{ $field['key'] }}" />
                                </flux:field>
                                @break
                            @case('color')
                                <flux:field>
                                    <flux:label>{{ $field['label'] }}</flux:label>
                                    <input type="color" wire:model.live="settings.{{ $field['key'] }}" class="h-10 w-full rounded-lg border border-zinc-200 dark:border-zinc-700" />
                                </flux:field>
                                @break
                            @case('select')
                                <flux:field>
                                    <flux:label>{{ $field['label'] }}</flux:label>
                                    <flux:select wire:model.live="settings.{{ $field['key'] }}">
                                        @foreach ($field['options'] as $value => $label)
                                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                                @break
                            @case('checkbox')
                                <flux:checkbox wire:model.live="settings.{{ $field['key'] }}" :label="$field['label']" />
                                @break
                        @endswitch
                    @endforeach
                </div>
            @else
                <flux:text class="text-center">{{ __('Select a section to edit its settings.') }}</flux:text>
            @endif
        </div>
    </div>
</div>
