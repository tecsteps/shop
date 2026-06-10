<header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-zinc-200 bg-white/90 px-4 backdrop-blur sm:px-6 dark:border-zinc-700 dark:bg-zinc-900/90">
    <flux:button
        variant="ghost"
        size="sm"
        icon="bars-3"
        class="lg:hidden"
        x-data
        x-on:click="$dispatch('toggle-admin-sidebar')"
        aria-label="{{ __('Open sidebar') }}"
    />

    {{-- Store selector --}}
    <flux:dropdown>
        <flux:button variant="ghost" size="sm" icon:trailing="chevron-down" data-test="store-selector">
            <span class="max-w-40 truncate">{{ $currentStore->name }}</span>
        </flux:button>

        <flux:menu>
            @foreach ($stores as $store)
                <flux:menu.item
                    wire:click="switchStore({{ $store->id }})"
                    :icon="$store->is($currentStore) ? 'check' : null"
                >
                    {{ $store->name }}
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>

    <flux:spacer />

    {{-- User menu --}}
    <flux:dropdown align="end">
        <flux:profile
            :initials="auth()->user()->initials()"
            :name="auth()->user()->name"
            icon-trailing="chevron-down"
            data-test="admin-user-menu"
        />

        <flux:menu>
            <div class="px-2 py-1.5">
                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate text-xs">{{ auth()->user()->email }}</flux:text>
            </div>

            <flux:menu.separator />

            @if (\Illuminate\Support\Facades\Route::has('admin.settings.index'))
                <flux:menu.item :href="route('admin.settings.index')" icon="cog-6-tooth" wire:navigate>
                    {{ __('Settings') }}
                </flux:menu.item>

                <flux:menu.separator />
            @endif

            <form method="POST" action="{{ route('admin.logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer"
                    data-test="admin-logout-button"
                >
                    {{ __('Log out') }}
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</header>
