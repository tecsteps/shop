<form wire:submit="save" class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <flux:heading size="xl">{{ $theme->name }}</flux:heading>
            <flux:text>Storefront theme editor</flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('admin.themes.index')" icon="arrow-left" wire:navigate>Back to themes</flux:button>
            <flux:button type="button" wire:click="refreshPreview" icon="arrow-path">Refresh preview</flux:button>
            <flux:button type="submit">Save</flux:button>
            <flux:button type="button" wire:click="publish" variant="primary">Save and publish</flux:button>
        </div>
    </div>

    <div class="grid min-h-[42rem] gap-6 xl:grid-cols-[18rem_minmax(0,1fr)_22rem]">
        <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="lg">Sections</flux:heading>

            <div class="mt-4 grid gap-2">
                @php($announcement = $sectionDefinitions['announcement'])
                <button type="button" wire:click="selectSection('announcement')" class="rounded-md px-3 py-2 text-left text-sm font-medium {{ $selectedSection === 'announcement' ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    {{ $announcement['label'] }}
                </button>

                <div class="mt-2 grid gap-2">
                    @foreach($homeSections as $index => $section)
                        @php($definition = $sectionDefinitions[$section['key']])
                        <div wire:key="theme-section-row-{{ $section['key'] }}" class="rounded-md border border-zinc-200 p-2 dark:border-zinc-800">
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="selectSection('{{ $section['key'] }}')" class="min-w-0 flex-1 rounded-md px-2 py-1.5 text-left text-sm font-medium {{ $selectedSection === $section['key'] ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                                    {{ $definition['label'] }}
                                </button>
                                <flux:checkbox wire:model.live="homeSections.{{ $index }}.enabled" aria-label="Show {{ $definition['label'] }}" />
                            </div>
                            <div class="mt-2 flex gap-1">
                                <flux:button type="button" size="sm" icon="arrow-up" wire:click="moveSectionUp('{{ $section['key'] }}')" aria-label="Move {{ $definition['label'] }} up" />
                                <flux:button type="button" size="sm" icon="arrow-down" wire:click="moveSectionDown('{{ $section['key'] }}')" aria-label="Move {{ $definition['label'] }} down" />
                            </div>
                        </div>
                    @endforeach
                </div>

                @php($footer = $sectionDefinitions['footer'])
                <button type="button" wire:click="selectSection('footer')" class="mt-2 rounded-md px-3 py-2 text-left text-sm font-medium {{ $selectedSection === 'footer' ? 'bg-zinc-950 text-white dark:bg-white dark:text-zinc-950' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                    {{ $footer['label'] }}
                </button>
            </div>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-zinc-100 p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex items-center justify-between gap-3">
                <flux:heading size="lg">Live preview</flux:heading>
                <a href="{{ $previewUrl }}" target="_blank" class="text-sm font-semibold text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white">Open</a>
            </div>
            <iframe title="Storefront preview" src="{{ $previewUrl }}" class="mt-4 h-[37rem] w-full rounded-md border border-zinc-200 bg-white shadow-sm dark:border-zinc-800"></iframe>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ $selectedSectionDefinition['label'] ?? 'Settings' }}</flux:heading>
                    @if($selectedSectionDefinition)
                        <flux:text>{{ $selectedSectionDefinition['description'] }}</flux:text>
                    @endif
                </div>
            </div>

            <div class="mt-5 grid gap-4">
                @switch($selectedSection)
                    @case('announcement')
                        <flux:checkbox wire:model.live="settings.announcement.enabled" label="Show announcement" />
                        <flux:input wire:model="settings.announcement.text" label="Text" />
                        <flux:input wire:model="settings.announcement.link" label="Link" />
                        @break

                    @case('hero')
                        <flux:input wire:model="settings.home.hero_heading" label="Heading" />
                        <flux:textarea wire:model="settings.home.hero_subheading" label="Subheading" rows="4" />
                        <flux:input wire:model="settings.home.hero_cta_label" label="Button label" />
                        <flux:input wire:model="settings.home.hero_cta_url" label="Button link" />
                        @break

                    @case('featured_collections')
                        <flux:input wire:model="settings.home.featured_collections_heading" label="Heading" />
                        <flux:textarea wire:model="settings.home.featured_collections_subheading" label="Subheading" rows="3" />
                        <flux:input wire:model="settings.home.featured_collections_count" type="number" min="2" max="4" label="Collection count" />
                        @break

                    @case('featured_products')
                        <flux:input wire:model="settings.home.featured_products_heading" label="Heading" />
                        <flux:input wire:model="settings.home.featured_products_count" type="number" min="4" max="8" label="Product count" />
                        @break

                    @case('newsletter')
                        <flux:input wire:model="settings.home.newsletter_heading" label="Heading" />
                        <flux:textarea wire:model="settings.home.newsletter_subheading" label="Subheading" rows="4" />
                        @break

                    @case('rich_text')
                        <flux:input wire:model="settings.home.rich_text_heading" label="Heading" />
                        <flux:textarea wire:model="settings.home.rich_text_html" label="HTML content" rows="10" />
                        @break

                    @case('footer')
                        <flux:input wire:model="settings.footer.contact_email" type="email" label="Contact email" />
                        @break
                @endswitch
            </div>
        </section>
    </div>
</form>
