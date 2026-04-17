<div class="flex items-center justify-between w-full">
    <div class="flex items-center gap-3">
        {{-- Store selector --}}
        <flux:dropdown>
            <flux:button variant="ghost" icon-trailing="chevron-down">
                {{ $currentStoreName }}
            </flux:button>

            <flux:menu>
                @foreach ($this->stores as $store)
                    <flux:menu.item wire:click="switchStore('{{ $store->id }}')">
                        {{ $store->name }}
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>
    </div>

    <div class="flex items-center gap-2">
        {{-- Notification bell --}}
        <flux:button variant="ghost" icon="bell" class="relative">
        </flux:button>

        {{-- User profile dropdown --}}
        <flux:dropdown position="bottom" align="end">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()" />

            <flux:menu>
                @if (\Illuminate\Support\Facades\Route::has('admin.settings.index'))
                    <flux:menu.item :href="route('admin.settings.index')" wire:navigate icon="cog-6-tooth">
                        Settings
                    </flux:menu.item>
                    <flux:separator />
                @endif
                <flux:menu.item
                    x-data
                    @click.prevent="$refs.logoutForm.submit()"
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
</div>
