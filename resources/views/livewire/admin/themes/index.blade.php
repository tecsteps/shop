<div>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($themes as $theme)
            <x-admin.card>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $theme->name }}</h3>
                <div class="mt-2 flex items-center gap-2">
                    <flux:badge size="sm" :variant="$theme->is_active ? 'primary' : 'outline'">
                        {{ $theme->is_active ? 'Active' : ucfirst($theme->status->value) }}
                    </flux:badge>
                </div>
                @unless($theme->is_active)
                    <flux:button wire:click="activate({{ $theme->id }})" variant="primary" size="sm" class="mt-4">Activate</flux:button>
                @endunless
            </x-admin.card>
        @empty
            <div class="col-span-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No themes found.</div>
        @endforelse
    </div>
</div>
