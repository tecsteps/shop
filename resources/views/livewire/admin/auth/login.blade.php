<div class="flex flex-col gap-6">
    <x-auth-header title="Admin login" description="Enter your merchant account credentials." />

    <form wire:submit="login" class="flex flex-col gap-6">
        <flux:input
            label="Email address"
            type="email"
            wire:model="email"
            required
            autofocus
            autocomplete="email"
            placeholder="admin@example.com"
        />

        <flux:input
            label="Password"
            type="password"
            wire:model="password"
            required
            autocomplete="current-password"
            placeholder="Password"
            viewable
        />

        <flux:checkbox wire:model="remember" label="Remember me" />

        <flux:button variant="primary" type="submit" class="w-full" data-test="admin-login-button">
            Log in
        </flux:button>
    </form>
</div>
