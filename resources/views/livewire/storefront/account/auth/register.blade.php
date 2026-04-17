<div class="flex flex-col gap-6">
    <flux:heading size="xl">Create an account</flux:heading>

    <form wire:submit="register" class="flex flex-col gap-4">
        <flux:input wire:model="name" label="Name" required />
        <flux:input wire:model="email" type="email" label="Email" required />
        <flux:input wire:model="password" type="password" label="Password" required />
        <flux:input wire:model="password_confirmation" type="password" label="Confirm password" required />
        <flux:checkbox wire:model="marketing_opt_in" label="Email me about news and offers" />
        <flux:button type="submit" variant="primary">Create account</flux:button>
    </form>
</div>
