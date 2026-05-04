<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:button :href="route('admin.themes.index')" wire:navigate variant="filled" icon="arrow-left">Themes</flux:button>

        <div class="flex gap-2">
            <flux:button type="button" wire:click="save" variant="filled">Save</flux:button>
            <flux:button type="button" wire:click="publish" variant="primary">Save and publish</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:callout color="green" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <div class="grid min-h-[720px] gap-4 xl:grid-cols-[240px_minmax(0,1fr)_340px]">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Sections</flux:heading>

            <div class="mt-4 space-y-2">
                @foreach ($sections as $key => $section)
                    <button
                        type="button"
                        wire:click="selectSection('{{ $key }}')"
                        class="block w-full rounded-lg px-3 py-2 text-left text-sm {{ $selectedSection === $key ? 'bg-blue-50 font-medium text-blue-950 dark:bg-blue-950/40 dark:text-blue-100' : 'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                    >
                        {{ $section['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-zinc-100 p-4 dark:border-zinc-700 dark:bg-zinc-950">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <flux:heading size="lg">Preview</flux:heading>
                    <flux:text>{{ $theme->name }}</flux:text>
                </div>
                <flux:button type="button" wire:click="refreshPreview" variant="filled" icon="arrow-path">Refresh</flux:button>
            </div>

            <iframe
                src="{{ $previewUrl }}"
                class="h-[620px] w-full rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-700"
                title="Storefront preview"
                x-data
                x-on:theme-preview-refresh.window="$el.contentWindow.location.reload()"
            ></iframe>
        </div>

        <form wire:submit="save" class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ $activeSection['label'] }}</flux:heading>

            <div class="mt-4 space-y-4">
                @foreach ($activeSection['fields'] as $field)
                    @php($model = 'settings.'.str_replace('.', '.', $field['key']))

                    @if ($field['type'] === 'checkbox')
                        <flux:checkbox wire:model="{{ $model }}" label="{{ $field['label'] }}" />
                    @elseif ($field['type'] === 'textarea')
                        <flux:textarea wire:model.live.debounce.500ms="{{ $model }}" label="{{ $field['label'] }}" rows="3" />
                    @elseif ($field['type'] === 'number')
                        <flux:input wire:model.live.debounce.500ms="{{ $model }}" type="number" min="0" label="{{ $field['label'] }}" />
                    @else
                        <flux:input wire:model.live.debounce.500ms="{{ $model }}" label="{{ $field['label'] }}" />
                    @endif
                @endforeach
            </div>
        </form>
    </div>
</section>
