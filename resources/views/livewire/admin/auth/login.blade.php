<div class="flex flex-col gap-6">
    <div class="text-center"><flux:heading size="xl">Admin sign in</flux:heading><flux:text>Manage your store from one place.</flux:text></div>
    <form wire:submit="login" class="flex flex-col gap-5">
        <flux:input wire:model="email" label="Email address" type="email" autocomplete="email" autofocus />
        <flux:input wire:model="password" label="Password" type="password" autocomplete="current-password" viewable />
        <div class="flex items-center justify-between gap-4"><flux:checkbox wire:model="remember" label="Remember me" /><flux:link :href="route('admin.password.request')" wire:navigate>Forgot password?</flux:link></div>
        <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">Sign in</flux:button>
    </form>
</div>