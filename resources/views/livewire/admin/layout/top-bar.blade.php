<header class="flex h-16 shrink-0 items-center justify-between border-b border-zinc-200 bg-white px-4 dark:border-zinc-700 dark:bg-zinc-800 sm:px-6">
    <div class="flex items-center gap-4">
        {{-- Mobile hamburger --}}
        <flux:button
            variant="ghost"
            icon="bars-3"
            class="lg:hidden"
            x-on:click="sidebarOpen = !sidebarOpen"
        />

        {{-- Store selector --}}
        <flux:dropdown>
            <flux:button variant="ghost" icon-trailing="chevron-down">
                {{ $currentStoreName }}
            </flux:button>

            <flux:menu>
                @foreach ($stores as $store)
                    <flux:menu.item wire:click="switchStore({{ $store->id }})">
                        {{ $store->name }}
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>
    </div>

    <div class="flex items-center gap-2">
        {{-- User profile dropdown --}}
        <flux:dropdown position="bottom" align="end">
            <flux:profile
                :name="auth()->user()?->name ?? 'User'"
                :initials="auth()->user()?->initials() ?? 'U'"
            />

            <flux:menu>
                <flux:menu.item href="{{ route('admin.settings.index') }}" wire:navigate icon="cog-6-tooth">
                    Settings
                </flux:menu.item>
                <flux:separator />
                <flux:menu.item
                    x-on:click="$refs.logoutForm.submit()"
                    icon="arrow-right-start-on-rectangle"
                >
                    Log out
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>

        <form x-ref="logoutForm" method="POST" action="{{ route('admin.logout') }}" class="hidden">
            @csrf
        </form>
    </div>
</header>
