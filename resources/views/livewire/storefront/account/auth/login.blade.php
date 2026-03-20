<div class="space-y-6">
    <div class="text-center">
        <flux:heading size="xl" level="1">Log in</flux:heading>
        <flux:text class="mt-2">Sign in to your account to view orders and manage your profile.</flux:text>
    </div>

    <form wire:submit="login" class="space-y-4">
        <flux:input wire:model="email" label="Email" type="email" placeholder="you@example.com" />

        <flux:input wire:model="password" label="Password" type="password" />

        <flux:checkbox wire:model="remember" label="Remember me" />

        <flux:button type="submit" variant="primary" class="w-full">Log in</flux:button>
    </form>

    <flux:text class="text-center">
        Don't have an account?
        <flux:link href="/account/register">Create one</flux:link>
    </flux:text>
</div>
