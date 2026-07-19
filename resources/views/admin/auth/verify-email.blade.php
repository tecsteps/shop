<x-admin::layouts.auth>
    <x-slot:title>Verify your email</x-slot:title>

    <flux:card>
        <flux:heading size="lg">Verify your email</flux:heading>
        <flux:text class="mt-2">
            Before getting started, please verify your email address by clicking the link we just emailed to you.
            If you did not receive the email, you can request another one below.
        </flux:text>

        @if (session('status') === 'verification-link-sent')
            <flux:callout variant="success" class="mt-4">
                <flux:callout.text>A new verification link has been sent to your email address.</flux:callout.text>
            </flux:callout>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <flux:button type="submit" variant="primary" class="w-full">Resend verification email</flux:button>
        </form>

        <form method="POST" action="{{ route('admin.logout') }}" class="mt-3">
            @csrf
            <flux:button type="submit" variant="ghost" class="w-full">Log out</flux:button>
        </form>
    </flux:card>
</x-admin::layouts.auth>
