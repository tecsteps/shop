<div class="flex flex-col gap-6">
    <flux:heading size="xl">Set your password</flux:heading>
    <p class="text-sm text-neutral-600 dark:text-neutral-400">
        Create a password to finish setting up your account.
    </p>

    <form wire:submit="setPassword" class="flex flex-col gap-4">
        <flux:input wire:model="email" type="email" label="Email" required />
        <flux:input wire:model="password" type="password" label="Password" required />
        <flux:input wire:model="password_confirmation" type="password" label="Confirm password" required />
        <flux:button type="submit" variant="primary">Set password and sign in</flux:button>
    </form>
</div>
