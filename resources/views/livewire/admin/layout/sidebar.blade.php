<div class="flex h-full flex-col">
    <div class="flex h-16 shrink-0 items-center border-b border-zinc-200 px-4 dark:border-zinc-800">
        <flux:brand href="{{ route('admin.dashboard') }}" wire:navigate name="{{ $store->name }}">
            <x-slot:logo>
                <flux:icon name="building-storefront" class="size-6 text-zinc-700 dark:text-zinc-200" />
            </x-slot:logo>
        </flux:brand>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Admin">
        @foreach ($navigation as $section)
            @if ($section['group'] !== null)
                <flux:separator class="my-3" />
                <p class="px-3 pt-1 pb-2 text-xs font-semibold tracking-wider text-zinc-500 uppercase dark:text-zinc-400">
                    {{ $section['group'] }}
                </p>
            @elseif (! $loop->first)
                <flux:separator class="my-3" />
            @endif

            <flux:navlist>
                @foreach ($section['items'] as $item)
                    <flux:navlist.item
                        :icon="$item['icon']"
                        :href="$this->hrefFor($item['route'])"
                        :current="$this->isActive($item['patterns'])"
                        wire:navigate
                        @click="sidebarOpen = false">
                        {{ $item['label'] }}
                    </flux:navlist.item>
                @endforeach
            </flux:navlist>
        @endforeach
    </nav>
</div>
