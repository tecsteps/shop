<div>
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" :href="route('admin.themes.index')" wire:navigate icon="arrow-left" />
            <flux:heading size="xl">{{ $theme->name }} - {{ __('Editor') }}</flux:heading>
        </div>
        <flux:button variant="primary" wire:click="save">{{ __('Save') }}</flux:button>
    </div>

    <div class="grid grid-cols-12 gap-6">
        {{-- Left panel: sections --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                <h3 class="text-sm font-medium text-zinc-500 uppercase mb-3">{{ __('Sections') }}</h3>
                <nav class="space-y-1">
                    @foreach($sections as $key => $label)
                        <button
                            wire:click="selectSection('{{ $key }}')"
                            class="w-full text-left px-3 py-2 rounded text-sm transition {{ $selectedSection === $key ? 'bg-accent text-white' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
                        >
                            {{ __($label) }}
                        </button>
                    @endforeach
                </nav>
            </div>
        </div>

        {{-- Center: preview --}}
        <div class="col-span-12 lg:col-span-5">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                <h3 class="text-sm font-medium text-zinc-500 uppercase mb-3">{{ __('Preview') }}</h3>
                <div class="aspect-[4/3] bg-zinc-50 dark:bg-zinc-800 rounded border border-zinc-200 dark:border-zinc-700 flex items-center justify-center">
                    <div class="text-center text-zinc-400">
                        <flux:icon name="eye" class="size-8 mx-auto mb-2" />
                        <p class="text-sm">{{ __('Live preview') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right panel: settings form --}}
        <div class="col-span-12 lg:col-span-4">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                <h3 class="text-sm font-medium text-zinc-500 uppercase mb-4">{{ __(ucfirst(str_replace('_', ' ', $selectedSection))) }}</h3>

                @if($selectedSection === 'announcement_bar')
                    <div class="space-y-4">
                        <flux:checkbox wire:model="settings.announcement_bar_enabled" label="{{ __('Enable announcement bar') }}" />
                        <flux:input wire:model="settings.announcement_bar_text" label="{{ __('Text') }}" />
                        <flux:input wire:model="settings.announcement_bar_link" label="{{ __('Link') }}" />
                        <flux:input wire:model="settings.announcement_bar_bg_color" label="{{ __('Background color') }}" type="color" />
                    </div>
                @elseif($selectedSection === 'header')
                    <div class="space-y-4">
                        <flux:checkbox wire:model="settings.sticky_header" label="{{ __('Sticky header') }}" />
                    </div>
                @elseif($selectedSection === 'hero')
                    <div class="space-y-4">
                        <flux:input wire:model="settings.hero_heading" label="{{ __('Heading') }}" />
                        <flux:input wire:model="settings.hero_subheading" label="{{ __('Subheading') }}" />
                        <flux:input wire:model="settings.hero_cta_text" label="{{ __('CTA text') }}" />
                        <flux:input wire:model="settings.hero_cta_link" label="{{ __('CTA link') }}" />
                    </div>
                @elseif($selectedSection === 'featured')
                    <div class="space-y-4">
                        <flux:input wire:model="settings.featured_collections_count" label="{{ __('Featured collections count') }}" type="number" />
                        <flux:input wire:model="settings.featured_products_count" label="{{ __('Featured products count') }}" type="number" />
                    </div>
                @elseif($selectedSection === 'social')
                    <div class="space-y-4">
                        <flux:input wire:model="settings.social_facebook" label="{{ __('Facebook') }}" />
                        <flux:input wire:model="settings.social_instagram" label="{{ __('Instagram') }}" />
                        <flux:input wire:model="settings.social_twitter" label="{{ __('Twitter') }}" />
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
