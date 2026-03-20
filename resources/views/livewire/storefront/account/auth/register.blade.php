<div class="space-y-6">
    <div class="text-center">
        <flux:heading size="xl" level="1">Create account</flux:heading>
        <flux:text class="mt-2">Register for an account to track orders and save addresses.</flux:text>
    </div>

    <form wire:submit="register" class="space-y-4">
        <flux:input wire:model="name" label="Name" placeholder="John Doe" />

        <flux:input wire:model="email" label="Email" type="email" placeholder="you@example.com" />

        <flux:input wire:model="password" label="Password" type="password" />

        <flux:input wire:model="password_confirmation" label="Confirm password" type="password" />

        <flux:checkbox wire:model="marketing_opt_in" label="Subscribe to marketing emails" />

        <flux:button type="submit" variant="primary" class="w-full">Register</flux:button>
    </form>

    <flux:text class="text-center">
        Already have an account?
        <flux:link href="/account/login">Log in</flux:link>
    </flux:text>
</div>
