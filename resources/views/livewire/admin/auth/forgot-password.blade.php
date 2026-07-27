<flux:card>
    <div class="mb-6 text-center">
        <flux:heading size="lg">Forgot password</flux:heading>
        <flux:text class="mt-1">Enter your email and we will send you a reset link.</flux:text>
    </div>

    @if ($linkSent)
        <flux:callout variant="success" class="mb-4">
            <flux:callout.text>If that email exists, we sent a reset link.</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="sendResetLink" class="space-y-4">
        <flux:field>
            <flux:label for="email">Email</flux:label>
            <flux:input id="email" type="email" wire:model="email" autocomplete="email" autofocus />
            <flux:error name="email" />
        </flux:field>

        <flux:button type="submit" variant="primary" class="w-full">Send reset link</flux:button>
    </form>

    <div class="mt-4 text-center">
        <flux:link href="{{ route('admin.login') }}" variant="subtle">Back to log in</flux:link>
    </div>
</flux:card>
