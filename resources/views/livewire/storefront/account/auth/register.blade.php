<div class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <h1 class="text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Create an account</h1>

    <form wire:submit="register" class="mt-8 space-y-5">
        <flux:field>
            <flux:label for="name">Name</flux:label>
            <flux:input id="name" type="text" wire:model="name" autocomplete="name" autofocus />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label for="email">Email</flux:label>
            <flux:input id="email" type="email" wire:model="email" autocomplete="email" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label for="password">Password</flux:label>
            <flux:input id="password" type="password" wire:model="password" autocomplete="new-password" />
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label for="password_confirmation">Confirm password</flux:label>
            <flux:input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" />
        </flux:field>

        <flux:checkbox wire:model="marketing_opt_in" label="Subscribe to marketing emails" />

        <flux:button type="submit" variant="primary" class="w-full">Create account</flux:button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
        Already have an account?
        <flux:link href="{{ route('storefront.account.login') }}">Log in</flux:link>
    </p>
</div>
