<div class="space-y-4 pb-8">
    <x-admin.breadcrumbs :items="[
        ['label' => __('Themes'), 'href' => route('admin.themes.index')],
        ['label' => $theme->name],
    ]" />

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.themes.index')" wire:navigate>
            {{ __('Back to themes') }}
        </flux:button>

        <div class="flex items-center gap-2">
            <x-admin.status-badge :status="$theme->status" />
            <flux:button variant="ghost" wire:click="save" data-test="save-theme-button">
                <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
            </flux:button>
            <flux:button variant="primary" wire:click="publish" data-test="publish-theme-button">
                {{ __('Save & publish') }}
            </flux:button>
        </div>
    </div>

    {{-- Three-panel layout --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[16rem_minmax(0,1fr)_20rem]">
        {{-- Left panel: sections --}}
        <x-admin.card class="!p-4">
            <flux:heading class="mb-3">{{ __('Theme settings') }}</flux:heading>

            <div class="space-y-1">
                @foreach (['header', 'colors', 'catalog', 'footer'] as $sectionKey)
                    <button
                        type="button"
                        wire:key="section-{{ $sectionKey }}"
                        wire:click="selectSection('{{ $sectionKey }}')"
                        data-test="section-{{ $sectionKey }}"
                        class="flex w-full cursor-pointer items-center rounded-lg px-2 py-1.5 text-left text-sm transition {{ $selectedSection === $sectionKey
                            ? 'bg-zinc-200/70 font-semibold text-zinc-900 dark:bg-zinc-800 dark:text-white'
                            : 'font-medium text-zinc-600 hover:bg-zinc-200/50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/70 dark:hover:text-white' }}"
                    >
                        {{ $this->sections()[$sectionKey]['label'] }}
                    </button>
                @endforeach
            </div>

            <flux:separator class="my-4" />

            <flux:heading class="mb-3">{{ __('Home page sections') }}</flux:heading>
            <flux:text class="mb-2 text-xs">{{ __('Drag to reorder, toggle to show or hide.') }}</flux:text>

            <div class="space-y-1" wire:sort="reorderSections">
                @foreach ($sectionOrder as $sectionKey)
                    <div
                        wire:key="home-section-{{ $sectionKey }}"
                        wire:sort.item="{{ $sectionKey }}"
                        class="flex cursor-grab items-center gap-2 rounded-lg px-2 py-1.5 {{ $selectedSection === $sectionKey ? 'bg-zinc-200/70 dark:bg-zinc-800' : 'hover:bg-zinc-200/50 dark:hover:bg-zinc-800/70' }}"
                    >
                        <flux:icon name="bars-3" variant="micro" class="shrink-0 text-zinc-400" />
                        <button
                            type="button"
                            wire:click="selectSection('{{ $sectionKey }}')"
                            data-test="section-{{ $sectionKey }}"
                            class="flex-1 cursor-pointer truncate text-left text-sm {{ $selectedSection === $sectionKey
                                ? 'font-semibold text-zinc-900 dark:text-white'
                                : 'font-medium text-zinc-600 dark:text-zinc-400' }} {{ ($enabledSections[$sectionKey] ?? false) ? '' : 'line-through opacity-50' }}"
                        >
                            {{ $this->orderableSections()[$sectionKey]['label'] }}
                        </button>
                        <button
                            type="button"
                            wire:click="toggleSection('{{ $sectionKey }}')"
                            aria-label="{{ __('Toggle :section', ['section' => $this->orderableSections()[$sectionKey]['label']]) }}"
                            data-test="toggle-section-{{ $sectionKey }}"
                            class="cursor-pointer text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                        >
                            <flux:icon :name="($enabledSections[$sectionKey] ?? false) ? 'eye' : 'eye-slash'" variant="micro" />
                        </button>
                    </div>
                @endforeach
            </div>
        </x-admin.card>

        {{-- Center panel: live preview --}}
        <div class="min-h-[480px] rounded-lg border border-zinc-200 bg-zinc-100 p-3 dark:border-zinc-700 dark:bg-zinc-950">
            @if ($this->previewUrl !== null)
                <iframe
                    src="{{ $this->previewUrl }}"
                    title="{{ __('Storefront preview') }}"
                    class="h-full min-h-[460px] w-full rounded-md border border-zinc-200 bg-white shadow dark:border-zinc-700"
                    loading="lazy"
                    data-test="theme-preview-iframe"
                ></iframe>
            @else
                <div class="flex h-full min-h-[460px] items-center justify-center">
                    <flux:text>{{ __('No storefront domain configured for preview.') }}</flux:text>
                </div>
            @endif
        </div>

        {{-- Right panel: settings for the selected section --}}
        <x-admin.card class="!p-4">
            @php($section = $this->sections()[$selectedSection] ?? null)

            @if ($section === null)
                <div class="flex h-full items-center justify-center py-12">
                    <flux:text>{{ __('Select a section to edit its settings.') }}</flux:text>
                </div>
            @else
                <flux:heading>{{ __(':section settings', ['section' => $section['label']]) }}</flux:heading>
                <flux:separator class="my-4" />

                <div class="space-y-4">
                    @foreach ($section['fields'] as $field)
                        <div wire:key="field-{{ $selectedSection }}-{{ $field['key'] }}">
                            @switch($field['type'])
                                @case('checkbox')
                                    <flux:checkbox wire:model="settings.{{ $field['key'] }}" :label="$field['label']" data-test="setting-{{ $field['key'] }}" />
                                    @break

                                @case('color')
                                    <flux:field>
                                        <flux:label>{{ $field['label'] }}</flux:label>
                                        <input
                                            type="color"
                                            wire:model.live.debounce.500ms="settings.{{ $field['key'] }}"
                                            class="h-9 w-16 cursor-pointer rounded border border-zinc-200 bg-white p-1 dark:border-zinc-600 dark:bg-zinc-800"
                                            data-test="setting-{{ $field['key'] }}"
                                        />
                                    </flux:field>
                                    @break

                                @case('select')
                                    <flux:field>
                                        <flux:label>{{ $field['label'] }}</flux:label>
                                        <flux:select wire:model="settings.{{ $field['key'] }}" data-test="setting-{{ $field['key'] }}">
                                            @foreach ($field['options'] ?? [] as $value => $label)
                                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </flux:field>
                                    @break

                                @case('textarea')
                                    <flux:field>
                                        <flux:label>{{ $field['label'] }}</flux:label>
                                        <flux:textarea wire:model.live.debounce.500ms="settings.{{ $field['key'] }}" rows="3" data-test="setting-{{ $field['key'] }}" />
                                    </flux:field>
                                    @break

                                @case('number')
                                    <flux:field>
                                        <flux:label>{{ $field['label'] }}</flux:label>
                                        <flux:input wire:model="settings.{{ $field['key'] }}" type="number" min="1" data-test="setting-{{ $field['key'] }}" />
                                    </flux:field>
                                    @break

                                @default
                                    <flux:field>
                                        <flux:label>{{ $field['label'] }}</flux:label>
                                        <flux:input wire:model.live.debounce.500ms="settings.{{ $field['key'] }}" data-test="setting-{{ $field['key'] }}" />
                                    </flux:field>
                            @endswitch
                        </div>
                    @endforeach
                </div>
            @endif
        </x-admin.card>
    </div>
</div>
