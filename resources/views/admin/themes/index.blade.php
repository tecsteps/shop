<div class="space-y-6">
    <x-admin.page-header title="Themes" description="Customize the storefront experience and publish one theme at a time." />
    @if($this->themes->isEmpty())
        <x-admin.empty-state title="No themes available" description="Add a theme package to begin customizing your storefront." />
    @else
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach($this->themes as $theme)@php $status=$theme->status instanceof BackedEnum ? $theme->status->value : $theme->status; @endphp
                <article wire:key="theme-{{ $theme->id }}" @class(['overflow-hidden rounded-2xl border bg-white shadow-sm dark:bg-zinc-900', 'border-blue-500 ring-2 ring-blue-500/20' => $status === 'published', 'border-zinc-200 dark:border-zinc-800' => $status !== 'published'])>
                    <div class="grid aspect-video place-items-center bg-gradient-to-br from-zinc-100 to-zinc-200 text-zinc-400 dark:from-zinc-800 dark:to-zinc-950"><flux:icon.paint-brush class="size-12" /></div>
                    <div class="space-y-4 p-5"><div class="flex items-start justify-between gap-4"><div><h2 class="font-semibold text-zinc-950 dark:text-white">{{ $theme->name }}</h2><p class="text-sm text-zinc-500">Version {{ $theme->version }}</p></div><x-admin.status-badge :status="$theme->status" /></div>
                        <div class="flex flex-wrap gap-2"><flux:button href="{{ url('/admin/themes/'.$theme->id.'/editor') }}" wire:navigate variant="primary" size="sm">Customize</flux:button>@if($status !== 'published')<flux:button type="button" wire:click="publishTheme({{ $theme->id }})" size="sm">Publish</flux:button>@endif<flux:button type="button" wire:click="duplicateTheme({{ $theme->id }})" variant="ghost" size="sm">Duplicate</flux:button>@if($status !== 'published')<flux:button type="button" wire:click="deleteTheme({{ $theme->id }})" wire:confirm="Delete this theme?" variant="ghost" size="sm" icon="trash" aria-label="Delete {{ $theme->name }}" />@endif</div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
