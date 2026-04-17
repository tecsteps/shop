<div>
    @if ($menu)
        <ul class="flex items-center gap-6" data-menu-handle="{{ $menu->handle }}">
            @foreach ($menu->items as $item)
                <li wire:key="nav-item-{{ $item->id }}">
                    <a href="{{ $item->resolveUrl() }}"
                        class="text-sm font-medium text-neutral-700 transition hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-white">
                        {{ $item->label }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
