<header class="sticky top-0 z-40 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95 sm:px-6 lg:px-8">
    <div class="flex min-w-0 items-center gap-2">
        <flux:button variant="ghost" icon="bars-3" class="lg:hidden" x-on:click="$dispatch('admin-sidebar-open')" aria-label="Open navigation" />
        <flux:dropdown>
            <flux:button variant="ghost" icon-trailing="chevron-down" class="max-w-52"><span class="truncate">{{ $currentStoreName }}</span></flux:button>
            <flux:menu>
                @foreach($stores as $store)
                    <flux:menu.item wire:click="switchStore('{{ $store->getKey() }}')" :icon="$store->name === $currentStoreName ? 'check' : null">{{ $store->name }}</flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>
    </div>
    <div class="flex items-center gap-1">
        <button type="button" class="relative grid size-10 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Notifications">
            <flux:icon.bell class="size-5" />
            @if($unreadNotificationCount > 0)<span class="absolute right-0.5 top-0.5 grid min-h-4 min-w-4 place-items-center rounded-full bg-red-600 px-1 text-[9px] font-bold text-white">{{ $unreadNotificationCount }}</span>@endif
        </button>
        <button type="button" x-data="{ dark: document.documentElement.classList.contains('dark') }" @click="dark=!dark;document.documentElement.classList.toggle('dark',dark);document.documentElement.style.colorScheme=dark?'dark':'light';localStorage.setItem('theme',dark?'dark':'light')" class="grid size-10 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Toggle dark mode"><flux:icon.moon class="size-5" /></button>
        <flux:dropdown position="bottom" align="end">
            <flux:button variant="ghost" class="gap-2 px-2">
                <span class="grid size-8 place-items-center rounded-full bg-slate-200 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-white">{{ $user?->initials() }}</span>
                <span class="hidden max-w-28 truncate text-sm sm:block">{{ $user?->name }}</span>
                <flux:icon.chevron-down class="size-4" />
            </flux:button>
            <flux:menu>
                <div class="px-3 py-2"><p class="text-sm font-medium">{{ $user?->name }}</p><p class="text-xs text-slate-500">{{ $user?->email }}</p></div>
                <flux:menu.separator />
                @if(in_array($role, ['owner', 'admin']))
                    <flux:menu.item href="{{ url('/admin/settings') }}" wire:navigate icon="cog-6-tooth">Settings</flux:menu.item>
                    <flux:menu.separator />
                @endif
                <flux:menu.item wire:click="logout" icon="arrow-right-start-on-rectangle">Log out</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>
</header>
