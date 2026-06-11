<div
    x-data="{ open: false }"
    x-on:toggle-admin-sidebar.window="open = ! open"
    x-on:keydown.escape.window="open = false"
>
    {{-- Mobile backdrop --}}
    <div
        x-show="open"
        x-transition.opacity
        x-on:click="open = false"
        class="fixed inset-0 z-40 bg-zinc-900/50 lg:hidden"
        style="display: none;"
        aria-hidden="true"
    ></div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-e border-zinc-200 bg-zinc-50 transition-transform duration-200 lg:translate-x-0 dark:border-zinc-700 dark:bg-zinc-900"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
        aria-label="{{ __('Admin navigation') }}"
    >
        <div class="flex h-14 shrink-0 items-center justify-between border-b border-zinc-200 px-4 dark:border-zinc-700">
            <flux:brand :href="route('admin.dashboard')" name="{{ config('app.name') }}" wire:navigate>
                <x-slot name="logo">
                    <div class="flex size-7 items-center justify-center rounded-md bg-zinc-900 dark:bg-white">
                        <flux:icon name="building-storefront" variant="micro" class="text-white dark:text-zinc-900" />
                    </div>
                </x-slot>
            </flux:brand>

            <flux:button
                variant="ghost"
                size="sm"
                icon="x-mark"
                class="lg:hidden"
                x-on:click="open = false"
                aria-label="{{ __('Close sidebar') }}"
            />
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            @foreach ($groups as $group)
                @if (! $loop->first)
                    <flux:separator class="my-3" />
                @endif

                @if ($group['label'] !== null)
                    <p class="px-2 pt-1 pb-1 text-xs font-semibold tracking-wide text-zinc-400 uppercase dark:text-zinc-500">
                        {{ $group['label'] }}
                    </p>
                @endif

                @foreach ($group['items'] as $item)
                    @if ($item['enabled'])
                        @php($isActive = request()->routeIs($item['active']))
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            x-on:click="open = false"
                            @if ($isActive) aria-current="page" @endif
                            class="flex items-center gap-3 rounded-lg px-2 py-1.5 text-sm transition {{ $isActive
                                ? 'bg-zinc-200/70 font-semibold text-zinc-900 dark:bg-zinc-800 dark:text-white'
                                : 'font-medium text-zinc-600 hover:bg-zinc-200/50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/70 dark:hover:text-white' }}"
                        >
                            <flux:icon :name="$item['icon']" variant="outline" class="size-5 shrink-0" />
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span
                            class="flex cursor-not-allowed items-center gap-3 rounded-lg px-2 py-1.5 text-sm font-medium text-zinc-400 dark:text-zinc-600"
                            title="{{ __('Coming soon') }}"
                        >
                            <flux:icon :name="$item['icon']" variant="outline" class="size-5 shrink-0" />
                            {{ $item['label'] }}
                        </span>
                    @endif
                @endforeach
            @endforeach
        </nav>
    </aside>
</div>
