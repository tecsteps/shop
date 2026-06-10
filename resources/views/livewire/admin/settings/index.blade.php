<div class="space-y-6">
    <x-admin.breadcrumbs :items="[['label' => __('Settings')]]" />

    <flux:heading size="xl" level="1">{{ __('Settings') }}</flux:heading>

    <x-admin.settings-tabs :active="$tab" />

    @switch($tab)
        @case('domains')
            <livewire:admin.settings.domains />
            @break

        @case('checkout')
            <livewire:admin.settings.checkout />
            @break

        @case('notifications')
            <livewire:admin.settings.notifications />
            @break

        @default
            <livewire:admin.settings.general />
    @endswitch
</div>
