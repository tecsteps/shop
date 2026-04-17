<div>
    <flux:heading size="xl">{{ __('Navigation') }}</flux:heading>

    <div class="mt-6 space-y-4">
        @forelse($menus as $menu)
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="lg">{{ $menu->title }}</flux:heading>
                <flux:text class="text-sm">{{ $menu->handle }}</flux:text>
                @if($menu->items->isNotEmpty())
                    <ul class="mt-2 space-y-1">
                        @foreach($menu->items->sortBy('position') as $item)
                            <li class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $item->label }} ({{ $item->type }})
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @empty
            <flux:text>{{ __('No navigation menus found.') }}</flux:text>
        @endforelse
    </div>
</div>
