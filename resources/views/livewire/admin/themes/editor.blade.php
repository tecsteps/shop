<div class="-m-6 sm:-mx-6 lg:-mx-8">
    <div class="flex items-center justify-between gap-2 border-b border-zinc-200 bg-white px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900 sm:px-6">
        <flux:button variant="ghost" href="{{ route('admin.themes.index') }}" wire:navigate>
            <flux:icon.arrow-left class="size-4" variant="outline" /> Back to themes
        </flux:button>
        <div class="flex gap-2">
            <flux:button variant="ghost" wire:click="save">Save</flux:button>
            <flux:button variant="primary" wire:click="publish">Save and publish</flux:button>
        </div>
    </div>

    <div class="flex min-h-[calc(100vh-8rem)] flex-col lg:flex-row">
        <div class="w-full shrink-0 border-b border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 lg:w-64 lg:border-b-0 lg:border-r">
            <flux:heading size="md">Sections</flux:heading>
            <div class="mt-3 flex flex-row gap-1 overflow-x-auto lg:flex-col">
                @foreach ($sections as $key => $section)
                    <button
                        type="button"
                        wire:click="selectSection('{{ $key }}')"
                        class="whitespace-nowrap rounded-lg px-3 py-2 text-left text-sm {{ $this->selectedSection === $key ? 'bg-zinc-900 font-semibold text-white dark:bg-white dark:text-zinc-900' : 'hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                        wire:key="section-{{ $key }}"
                    >{{ $section['label'] }}</button>
                @endforeach
            </div>
        </div>

        <div class="min-h-96 flex-1 bg-zinc-200 p-4 dark:bg-zinc-800">
            <div x-data="{ key: 0 }" x-on:refresh-theme-preview.window="key++">
                <iframe :key="key" src="{{ $previewUrl }}" title="Storefront preview" class="h-[calc(100vh-12rem)] min-h-96 w-full rounded-lg border border-zinc-300 bg-white shadow dark:border-zinc-700"></iframe>
            </div>
        </div>

        <div class="w-full shrink-0 border-t border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 lg:w-80 lg:border-l lg:border-t-0">
            @if ($this->selectedSection === null)
                <p class="mt-8 text-center text-sm text-zinc-500">Select a section to edit its settings.</p>
            @else
                <flux:heading size="md">{{ $sections[$this->selectedSection]['label'] }} settings</flux:heading>
                <flux:separator class="my-3" />
                <div class="flex flex-col gap-3">
                    @foreach ($sections[$this->selectedSection]['fields'] as $field)
                        <div wire:key="field-{{ $field['key'] }}">
                            @if ($field['type'] === 'text')
                                <flux:input wire:model.live.debounce.500ms="sectionSettings.{{ $field['key'] }}" :label="$field['label']" />
                            @elseif ($field['type'] === 'textarea')
                                <flux:textarea wire:model.live.debounce.500ms="sectionSettings.{{ $field['key'] }}" :label="$field['label']" rows="3" />
                            @elseif ($field['type'] === 'color')
                                <flux:field>
                                    <flux:label>{{ $field['label'] }}</flux:label>
                                    <input type="color" wire:model.live="sectionSettings.{{ $field['key'] }}" class="h-10 w-full cursor-pointer rounded-lg border border-zinc-300 dark:border-zinc-600" aria-label="{{ $field['label'] }}" />
                                </flux:field>
                            @elseif ($field['type'] === 'select')
                                <flux:field>
                                    <flux:label>{{ $field['label'] }}</flux:label>
                                    <flux:select wire:model.live="sectionSettings.{{ $field['key'] }}">
                                        @foreach ($field['options'] as $value => $label)
                                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                            @elseif ($field['type'] === 'checkbox')
                                <label class="flex items-center gap-2 text-sm">
                                    <flux:checkbox wire:model.live="sectionSettings.{{ $field['key'] }}" />
                                    <span>{{ $field['label'] }}</span>
                                </label>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
