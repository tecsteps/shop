<div class="flex flex-col gap-6">
    <div class="text-center"><flux:heading size="xl">Forgot password</flux:heading><flux:text>We'll email you a secure reset link.</flux:text></div>
    @if ($status)<flux:callout variant="success">{{ $status }}</flux:callout>@endif
    <form wire:submit="sendResetLink" class="flex flex-col gap-5"><flux:input wire:model="email" label="Email address" type="email" autofocus /><flux:button type="submit" variant="primary" class="w-full">Send reset link</flux:button></form>
    <flux:link :href="route('admin.login')" wire:navigate class="text-center">Back to sign in</flux:link>
</div>