<div class="mx-auto max-w-md px-4 py-16 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Create an account</h1>
    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
        Already have an account?
        <a href="{{ route('storefront.account.login') }}" wire:navigate class="text-blue-600 hover:underline dark:text-blue-400">Log in</a>
    </p>

    <form wire:submit.prevent="register" class="mt-8 space-y-4">
        <flux:field>
            <flux:label for="name">Full name</flux:label>
            <flux:input id="name" wire:model="name" autofocus required />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label for="email">Email</flux:label>
            <flux:input id="email" type="email" wire:model="email" required />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label for="password">Password</flux:label>
            <flux:input id="password" type="password" wire:model="password" required />
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label for="password_confirmation">Confirm password</flux:label>
            <flux:input id="password_confirmation" type="password" wire:model="password_confirmation" required />
        </flux:field>

        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
            <input type="checkbox" wire:model="marketingOptIn" class="rounded border-zinc-300 text-blue-600 dark:border-zinc-700" />
            Keep me updated with news and offers
        </label>

        <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" wire:target="register">
            <span wire:loading.remove wire:target="register">Create account</span>
            <span wire:loading wire:target="register">Creating account...</span>
        </flux:button>
    </form>
</div>
