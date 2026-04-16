<div class="mx-auto max-w-md">
    <flux:heading size="xl" class="mb-6 text-center">Sign in</flux:heading>
    <form wire:submit="login" class="flex flex-col gap-4 rounded-xl bg-white p-6 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
        <flux:input type="email" label="Email" wire:model="email" required autofocus autocomplete="email" />
        <flux:input type="password" label="Password" wire:model="password" required autocomplete="current-password" viewable />
        <flux:checkbox label="Remember me" wire:model="remember" />
        <flux:button type="submit" variant="primary" class="w-full">Sign in</flux:button>
    </form>
    <p class="mt-4 text-center text-sm text-zinc-500">
        New here?
        <a href="{{ route('storefront.account.register') }}" class="font-medium text-zinc-900 underline dark:text-white" wire:navigate>Create an account</a>
    </p>
</div>
