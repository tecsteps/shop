<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
    <h1 class="text-2xl font-semibold tracking-tight">Choose a new password</h1>
    <form wire:submit="resetPassword" class="mt-6 space-y-5">
        <flux:input wire:model="email" label="Email address" type="email" autocomplete="username" required />
        <flux:input wire:model="password" label="New password" type="password" autocomplete="new-password" viewable required />
        <flux:input wire:model="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" viewable required />
        <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">Reset password</flux:button>
    </form>
</section>
