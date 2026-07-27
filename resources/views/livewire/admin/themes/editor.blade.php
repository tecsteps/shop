<div class="space-y-4">
    {{-- Top toolbar (spec 03 §12.2) --}}
    <div class="flex items-center justify-between gap-4">
        <flux:button variant="ghost" icon="arrow-left" :href="route('admin.themes.index')" wire:navigate>Back to themes</flux:button>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" wire:click="save" wire:loading.attr="disabled" wire:target="save">Save</flux:button>
            <flux:button variant="primary" wire:click="saveAndPublish" wire:loading.attr="disabled" wire:target="saveAndPublish">Save and publish</flux:button>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[264px_1fr_320px]">
        {{-- Left panel: sections list (spec 03 §12.2) --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">Sections</flux:heading>

            <ul class="mt-3 space-y-1">
                @foreach ($sectionLabels as $key => $label)
                    <li wire:key="section-{{ $key }}" class="flex items-center gap-1">
                        <button
                            type="button"
                            wire:click="selectSection('{{ $key }}')"
                            class="flex-1 rounded-md px-3 py-2 text-left text-sm {{ $selectedSection === $key ? 'bg-zinc-100 font-semibold text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50' }}"
                        >{{ $label }}</button>

                        @if (array_key_exists('enabled', $settings[$key] ?? []))
                            <flux:button
                                size="sm"
                                variant="ghost"
                                :icon="$settings[$key]['enabled'] ? 'eye' : 'eye-slash'"
                                wire:click="toggleSection('{{ $key }}')"
                                aria-label="Toggle {{ $label }} visibility"
                            />
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Center panel: live preview (spec 03 §12.2). Simplified: the iframe
             always shows the live storefront home page; draft-theme preview
             tokens are out of scope. --}}
        <div class="min-h-[70vh] rounded-lg bg-zinc-100 p-4 dark:bg-zinc-800">
            <iframe src="{{ $previewUrl }}" title="Storefront preview" class="size-full min-h-[66vh] rounded-lg border border-zinc-200 bg-white shadow dark:border-zinc-700"></iframe>
        </div>

        {{-- Right panel: settings form (spec 03 §12.2) --}}
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md">{{ $sectionLabels[$selectedSection] }}</flux:heading>
            <flux:separator class="my-3" />

            <div class="space-y-4">
                @if ($selectedSection === 'announcement')
                    <flux:switch wire:model="settings.announcement.enabled" label="Show announcement" />
                    <flux:field>
                        <flux:label>Text</flux:label>
                        <flux:input wire:model.blur="settings.announcement.text" placeholder="Free shipping over 50!" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Link</flux:label>
                        <flux:input wire:model.blur="settings.announcement.link" placeholder="/collections/sale" />
                    </flux:field>

                @elseif ($selectedSection === 'colors')
                    @foreach (['primary' => 'Primary', 'secondary' => 'Secondary', 'accent' => 'Accent'] as $colorKey => $colorLabel)
                        <flux:field wire:key="color-{{ $colorKey }}">
                            <flux:label>{{ $colorLabel }}</flux:label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model.blur="settings.colors.{{ $colorKey }}" class="h-9 w-12 cursor-pointer rounded border border-zinc-300 dark:border-zinc-600" />
                                <flux:input wire:model.blur="settings.colors.{{ $colorKey }}" class="flex-1" />
                            </div>
                        </flux:field>
                    @endforeach

                @elseif ($selectedSection === 'hero')
                    <flux:switch wire:model="settings.hero.enabled" label="Show hero" />
                    <flux:field>
                        <flux:label>Heading</flux:label>
                        <flux:input wire:model.blur="settings.hero.heading" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Subheading</flux:label>
                        <flux:textarea wire:model.blur="settings.hero.subheading" rows="3" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Button label</flux:label>
                        <flux:input wire:model.blur="settings.hero.cta_label" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Button URL</flux:label>
                        <flux:input wire:model.blur="settings.hero.cta_url" />
                    </flux:field>

                @elseif ($selectedSection === 'featured_collections')
                    <flux:switch wire:model="settings.featured_collections.enabled" label="Show featured collections" />
                    <flux:field>
                        <flux:label>Number of collections</flux:label>
                        <flux:input type="number" min="1" max="12" wire:model.blur="settings.featured_collections.count" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Collection handles</flux:label>
                        <flux:input wire:model.blur="featuredCollectionHandles" placeholder="summer, new-arrivals" />
                        <flux:description>Comma-separated handles. Empty uses the newest collections.</flux:description>
                    </flux:field>

                @elseif ($selectedSection === 'featured_products')
                    <flux:switch wire:model="settings.featured_products.enabled" label="Show featured products" />
                    <flux:field>
                        <flux:label>Number of products</flux:label>
                        <flux:input type="number" min="1" max="24" wire:model.blur="settings.featured_products.count" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Collection handle</flux:label>
                        <flux:input wire:model.blur="settings.featured_products.collection_handle" placeholder="all" />
                    </flux:field>

                @elseif ($selectedSection === 'newsletter')
                    <flux:switch wire:model="settings.newsletter.enabled" label="Show newsletter signup" />

                @elseif ($selectedSection === 'rich_text')
                    <flux:switch wire:model="settings.rich_text.enabled" label="Show rich text" />
                    <flux:field>
                        <flux:label>Content</flux:label>
                        <flux:textarea wire:model.blur="settings.rich_text.html" rows="6" />
                    </flux:field>

                @elseif ($selectedSection === 'footer')
                    <flux:field>
                        <flux:label>About text</flux:label>
                        <flux:textarea wire:model.blur="settings.footer.about" rows="4" />
                    </flux:field>

                @elseif ($selectedSection === 'seo')
                    <flux:field>
                        <flux:label>Meta description</flux:label>
                        <flux:textarea wire:model.blur="settings.seo.description" rows="3" />
                    </flux:field>
                @endif
            </div>
        </div>
    </div>
</div>
