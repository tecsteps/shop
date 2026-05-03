<div class="mx-auto max-w-md px-4 py-16 sm:px-6 lg:px-8">
    <h1 class="text-center text-3xl font-semibold tracking-normal">Choose a new password</h1>

    <form wire:submit="resetPassword" class="mt-8 flex flex-col gap-4 rounded-lg border border-zinc-200 p-6 dark:border-zinc-800">
        <div>
            <label for="customer-reset-password-email" class="text-sm font-medium">Email</label>
            <input id="customer-reset-password-email" wire:model="email" type="email" autocomplete="email" class="mt-2 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            @error('email') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="customer-new-password" class="text-sm font-medium">Password</label>
            <input id="customer-new-password" wire:model="password" type="password" autocomplete="new-password" class="mt-2 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            @error('password') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="customer-new-password-confirmation" class="text-sm font-medium">Confirm password</label>
            <input id="customer-new-password-confirmation" wire:model="passwordConfirmation" type="password" autocomplete="new-password" class="mt-2 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            @error('passwordConfirmation') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded-md bg-zinc-950 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">
            Reset password
        </button>
    </form>
</div>
