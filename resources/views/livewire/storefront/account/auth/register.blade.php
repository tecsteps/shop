<div class="mx-auto max-w-md px-4 py-16">
    <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Join us</p>
    <h1 class="mt-2 text-4xl font-bold">Create your account</h1>
    <form wire:submit="register" class="mt-8 space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="first-name" class="text-sm font-medium">First name</label><input id="first-name" autocomplete="given-name" wire:model="firstName" class="mt-2 w-full rounded-lg border border-zinc-300 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900"></div>
            <div><label for="last-name" class="text-sm font-medium">Last name</label><input id="last-name" autocomplete="family-name" wire:model="lastName" class="mt-2 w-full rounded-lg border border-zinc-300 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900"></div>
        </div>
        <div><label for="register-email" class="text-sm font-medium">Email</label><input id="register-email" type="email" autocomplete="email" wire:model="email" class="mt-2 w-full rounded-lg border border-zinc-300 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900"></div>
        <div><label for="register-password" class="text-sm font-medium">Password</label><input id="register-password" type="password" autocomplete="new-password" wire:model="password" class="mt-2 w-full rounded-lg border border-zinc-300 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900"></div>
        <div><label for="register-password-confirmation" class="text-sm font-medium">Confirm password</label><input id="register-password-confirmation" type="password" autocomplete="new-password" wire:model="passwordConfirmation" class="mt-2 w-full rounded-lg border border-zinc-300 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900"></div>
        <label class="flex items-start gap-3 text-sm"><input type="checkbox" wire:model="marketingOptIn" class="mt-1"><span>Send me occasional product updates and offers.</span></label>
        @error('*')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        <button class="w-full rounded-full bg-blue-600 px-5 py-3 font-semibold text-white">Create account</button>
    </form>
</div>
