<div class="mx-auto max-w-md p-8">
    <flux:heading size="xl">Create an account</flux:heading>

    <form wire:submit="register" class="mt-6 space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>First name</flux:label>
                <flux:input wire:model="first_name" required autofocus />
                <flux:error name="first_name" />
            </flux:field>

            <flux:field>
                <flux:label>Last name</flux:label>
                <flux:input wire:model="last_name" required />
                <flux:error name="last_name" />
            </flux:field>
        </div>

        <flux:field>
            <flux:label>Email</flux:label>
            <flux:input wire:model="email" type="email" required autocomplete="email" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label>Password</flux:label>
            <flux:input wire:model="password" type="password" required autocomplete="new-password" />
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label>Confirm password</flux:label>
            <flux:input wire:model="password_confirmation" type="password" required autocomplete="new-password" />
        </flux:field>

        <flux:button type="submit" variant="primary" class="w-full">Create account</flux:button>

        <flux:text class="text-center">
            Already have an account? <a href="{{ route('account.login') }}" class="underline">Sign in</a>
        </flux:text>
    </form>
</div>
