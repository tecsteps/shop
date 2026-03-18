<x-layouts::app.sidebar :title="__('Admin Dashboard')">
    <flux:main>
        <div class="flex items-center justify-between">
            <flux:heading size="xl">{{ __('Admin Dashboard') }}</flux:heading>
            <livewire:admin.auth.logout />
        </div>
    </flux:main>
</x-layouts::app.sidebar>
