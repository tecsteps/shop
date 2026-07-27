<div class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <h1 class="text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Forgot password</h1>
    <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
        Enter your email and we will send you a reset link.
    </p>

    @if ($linkSent)
        <flux:callout variant="success" class="mt-6">
            <flux:callout.text>If that email exists, we sent a reset link.</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="sendResetLink" class="mt-8 space-y-5">
        <flux:field>
            <flux:label for="email">Email</flux:label>
            <flux:input id="email" type="email" wire:model="email" autocomplete="email" autofocus />
            <flux:error name="email" />
        </flux:field>

        <flux:button type="submit" variant="primary" class="w-full">Send reset link</flux:button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
        <flux:link href="{{ route('storefront.account.login') }}">Back to log in</flux:link>
    </p>
</div>
