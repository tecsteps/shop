<header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-zinc-200 bg-white px-4 dark:border-zinc-800 dark:bg-zinc-900 sm:px-6">
    <div class="flex items-center gap-2">
        <flux:button variant="ghost" size="sm" x-data="" x-on:click="$dispatch('toggle-admin-sidebar')" class="lg:hidden" aria-label="Toggle navigation">
            <flux:icon.bars-3 class="size-5" variant="outline" />
        </flux:button>

        <flux:dropdown position="bottom" align="start">
            <flux:button variant="ghost" icon-trailing="chevron-down">
                {{ $currentStore?->name ?? 'Select store' }}
            </flux:button>
            <flux:menu>
                @foreach ($stores as $store)
                    <flux:menu.item wire:click="switchStore({{ $store->getKey() }})" :active="$store->getKey() === $currentStore?->getKey()">
                        {{ $store->name }}
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>
    </div>

    <div class="flex items-center gap-1">
        <flux:dropdown position="bottom" align="end">
            <flux:profile :name="$user?->name" :initials="collect(explode(' ', (string) $user?->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('')" />
            <flux:menu>
                <flux:menu.item href="{{ route('admin.settings.index') }}" wire:navigate>Settings</flux:menu.item>
                <flux:menu.separator />
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <flux:menu.item as="button" type="submit">Log out</flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </div>
</header>
