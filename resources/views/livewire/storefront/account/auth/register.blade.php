<div class="mx-auto max-w-md">
    <flux:heading size="xl" class="mb-6 text-center">Create account</flux:heading>
    <form wire:submit="register" class="flex flex-col gap-4 rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
        <flux:input label="Name" wire:model="name" required />
        <flux:input type="email" label="Email" wire:model="email" required autocomplete="email" />
        <flux:input type="password" label="Password" wire:model="password" required autocomplete="new-password" viewable />
        <flux:input type="password" label="Confirm password" wire:model="passwordConfirmation" required />
        <flux:checkbox label="Send me marketing emails" wire:model="marketingOptIn" />
        <flux:button type="submit" variant="primary" class="w-full">Create account</flux:button>
    </form>
    <p class="mt-4 text-center text-sm text-zinc-500">
        Already have an account?
        <a href="{{ route('storefront.account.login') }}" class="font-medium text-zinc-900 underline dark:text-white" wire:navigate>Sign in</a>
    </p>
</div>
