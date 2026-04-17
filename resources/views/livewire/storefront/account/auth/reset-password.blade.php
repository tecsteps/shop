<div class="flex flex-col gap-6">
    <flux:heading size="xl">Reset your password</flux:heading>

    <form wire:submit="resetPassword" class="flex flex-col gap-4">
        <flux:input wire:model="email" type="email" label="Email" required />
        <flux:input wire:model="password" type="password" label="New password" required />
        <flux:input wire:model="password_confirmation" type="password" label="Confirm new password" required />
        <flux:button type="submit" variant="primary">Reset password</flux:button>
    </form>
</div>
