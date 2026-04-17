<div class="flex flex-col gap-6">
    <h1 class="text-2xl font-bold">{{ __('Customer Login') }}</h1>

    <form wire:submit="authenticate" class="flex flex-col gap-6">
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
            viewable
        />

        <flux:checkbox wire:model="remember" :label="__('Remember me')" />

        <flux:button variant="primary" type="submit" class="w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="authenticate">{{ __('Log in') }}</span>
            <span wire:loading wire:target="authenticate">{{ __('Logging in...') }}</span>
        </flux:button>
    </form>
</div>
