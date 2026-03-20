<div class="flex flex-col gap-6">
    <h1 class="text-2xl font-bold">{{ __('Create Account') }}</h1>

    <form wire:submit="register" class="flex flex-col gap-6">
        <flux:input
            wire:model="name"
            :label="__('Name')"
            type="text"
            required
            autofocus
            autocomplete="name"
        />

        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autocomplete="email"
            placeholder="email@example.com"
        />

        <flux:input
            wire:model="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="new-password"
            viewable
        />

        <flux:input
            wire:model="password_confirmation"
            :label="__('Confirm Password')"
            type="password"
            required
            autocomplete="new-password"
        />

        <flux:checkbox wire:model="marketing_opt_in" :label="__('I agree to receive marketing emails')" />

        <flux:button variant="primary" type="submit" class="w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="register">{{ __('Create Account') }}</span>
            <span wire:loading wire:target="register">{{ __('Creating...') }}</span>
        </flux:button>
    </form>
</div>
