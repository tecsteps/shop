<div class="flex flex-col gap-6">
    <flux:heading size="xl">Sign in</flux:heading>

    <form wire:submit="authenticate" class="flex flex-col gap-4">
        <flux:input wire:model="email" type="email" label="Email" required />
        <flux:input wire:model="password" type="password" label="Password" required />
        <flux:checkbox wire:model="remember" label="Remember me" />
        <flux:button type="submit" variant="primary">Log in</flux:button>
    </form>
</div>
