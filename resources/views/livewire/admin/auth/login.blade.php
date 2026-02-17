<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Admin Login')" :description="__('Enter your email and password to access the admin panel')" />

        @if (session('status'))
            <x-auth-session-status class="text-center" :status="session('status')" />
        @endif

        <form wire:submit="login" class="flex flex-col gap-6">
            <flux:input
                wire:model="email"
                :label="__('Email address')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            <flux:checkbox wire:model="remember" :label="__('Remember me')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>
