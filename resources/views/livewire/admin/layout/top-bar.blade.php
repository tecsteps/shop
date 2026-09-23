<div class="flex min-w-0 flex-1 items-center justify-between gap-3">
    <div class="min-w-0">
        @if ($stores->count() > 1)
            <details class="group relative">
                <summary class="flex min-h-11 max-w-[min(18rem,45vw)] cursor-pointer list-none items-center gap-2 rounded-xl px-3 text-left hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:hover:bg-zinc-800" aria-label="Choose current store">
                    <span class="min-w-0"><span class="block text-xs text-zinc-500 dark:text-zinc-400">Current store</span><span class="block truncate text-sm font-semibold">{{ $currentStoreName }}</span></span>
                    <flux:icon name="chevron-down" class="size-4 shrink-0 text-zinc-500 transition group-open:rotate-180" />
                </summary>
                <div class="absolute left-0 top-full z-50 mt-2 w-64 overflow-hidden rounded-xl border border-zinc-200 bg-white p-1 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Your stores</p>
                    @foreach ($stores as $store)
                        <button type="button" wire:key="admin-store-switch-{{ $store->id }}" wire:click="switchStore('{{ $store->id }}')" @if ($currentStoreId === (int) $store->id) aria-current="true" @endif class="flex min-h-10 w-full items-center justify-between gap-3 rounded-lg px-3 text-left text-sm hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:hover:bg-zinc-800">
                            <span class="truncate">{{ $store->name }}</span>
                            @if ($currentStoreId === (int) $store->id)<flux:icon name="check" class="size-4 text-blue-600 dark:text-blue-400" />@endif
                        </button>
                    @endforeach
                </div>
            </details>
        @else
            <div class="min-w-0 px-3"><span class="block text-xs text-zinc-500 dark:text-zinc-400">Current store</span><span class="block truncate text-sm font-semibold">{{ $currentStoreName }}</span></div>
        @endif
    </div>

    <div class="flex shrink-0 items-center gap-1 sm:gap-2">
        @if ($unreadNotificationCount > 0)
            <button type="button" class="relative inline-flex size-10 items-center justify-center rounded-xl text-zinc-600 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="Notifications, {{ $unreadNotificationCount }} unread">
                <flux:icon name="bell" class="size-5" />
                <span class="absolute right-1 top-1 flex min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-semibold leading-4 text-white" aria-hidden="true">{{ $unreadNotificationCount }}</span>
            </button>
        @else
            <span class="inline-flex size-10 items-center justify-center rounded-xl text-zinc-400 dark:text-zinc-500" aria-label="No new notifications"><flux:icon name="bell" class="size-5" /></span>
        @endif
        <button type="button" x-on:click="const dark = !document.documentElement.classList.contains('dark'); document.documentElement.classList.toggle('dark', dark); localStorage.setItem('theme', dark ? 'dark' : 'light')" class="inline-flex size-10 items-center justify-center rounded-xl text-zinc-600 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="Toggle dark mode">
            <flux:icon name="sun" class="size-5 dark:hidden" /><flux:icon name="moon" class="hidden size-5 dark:block" />
        </button>
        <details class="group relative">
            <summary class="flex min-h-10 cursor-pointer list-none items-center gap-2 rounded-xl px-2 hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:hover:bg-zinc-800" aria-label="Account menu">
                <span class="hidden max-w-36 text-right sm:block"><span class="block truncate text-sm font-medium">{{ auth()->user()?->name ?? 'Administrator' }}</span><span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()?->email ?? '' }}</span></span>
                <span class="inline-flex size-9 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-800 dark:bg-blue-950 dark:text-blue-200" aria-hidden="true">{{ auth()->user()?->initials() ?? 'A' }}</span>
                <flux:icon name="chevron-down" class="hidden size-4 text-zinc-500 sm:block" />
            </summary>
            <div class="absolute right-0 top-full z-50 mt-2 w-52 rounded-xl border border-zinc-200 bg-white p-1 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                <a href="{{ route('admin.settings') }}" wire:navigate class="flex min-h-10 items-center rounded-lg px-3 text-sm hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-blue-600 dark:hover:bg-zinc-800">Settings</a>
                <div class="my-1 border-t border-zinc-200 dark:border-zinc-700"></div>
                <form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit" class="flex min-h-10 w-full items-center rounded-lg px-3 text-left text-sm text-rose-700 hover:bg-rose-50 focus-visible:outline-2 focus-visible:outline-rose-600 dark:text-rose-300 dark:hover:bg-rose-950/40">Log out</button></form>
            </div>
        </details>
    </div>
</div>
