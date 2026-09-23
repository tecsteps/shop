<nav id="admin-navigation" aria-label="Admin navigation" class="min-h-0 flex-1 overflow-y-auto px-3 py-5">
    @foreach ($groups as $group)
        <div @class(['mb-6', !$loop->first => 'border-t border-zinc-200 pt-5 dark:border-zinc-800'])>
            <h2 class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500 dark:text-zinc-400">{{ $group['label'] }}</h2>
            <ul class="grid gap-1">
                @foreach ($group['items'] as $item)
                    @php($isCurrent = request()->routeIs($item['route']))
                    <li>
                        <a href="{{ $item['href'] }}" wire:navigate x-on:click="mobileOpen = false" @if ($isCurrent) aria-current="page" @endif @class([
                            'flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium transition',
                            'bg-blue-50 text-blue-800 dark:bg-blue-950/60 dark:text-blue-200' => $isCurrent,
                            'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white' => !$isCurrent,
                        ])>
                            <flux:icon name="{{ $item['icon'] }}" class="size-5 shrink-0" />
                            <span>{{ $item['label'] }}</span>
                            @if ($isCurrent)<span class="ml-auto size-1.5 rounded-full bg-blue-600 dark:bg-blue-400" aria-hidden="true"></span>@endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
