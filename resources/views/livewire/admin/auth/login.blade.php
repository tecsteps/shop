<div class="space-y-6">
    <div class="text-center">
        <flux:heading size="xl" level="1">Sign in</flux:heading>
        <flux:text class="mt-2">Sign in to the admin panel.</flux:text>
    </div>

    <form wire:submit="login" class="space-y-4">
        <flux:input wire:model="email" label="Email" type="email" placeholder="admin@example.com" />

        <flux:input wire:model="password" label="Password" type="password" />

        <flux:checkbox wire:model="remember" label="Remember me" />

        <flux:button type="submit" variant="primary" class="w-full">Sign in</flux:button>
    </form>
</div>
