
@section('title', 'Create an account')
<div>
<section class="mx-auto max-w-xl px-4 py-12 sm:px-6 sm:py-16">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-9 dark:border-stone-800 dark:bg-stone-950">
            <p class="text-center text-xs font-semibold uppercase tracking-[0.18em] text-blue-800 dark:text-blue-300">A little more convenient</p><h1 class="mt-2 text-center text-2xl font-semibold tracking-tight sm:text-3xl">Create an account</h1><p class="mt-2 text-center text-sm text-stone-600 dark:text-stone-300">Keep your orders and addresses together.</p>
            <form wire:submit="register" class="mt-7 grid gap-4">
                <div><label for="register-name" class="mb-1.5 block text-sm font-medium">Name <span class="text-rose-700" aria-hidden="true">*</span></label><input id="register-name" name="name" type="text" wire:model.blur="name" autocomplete="name" required class="storefront-input">@error('name')<p class="mt-1.5 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror</div>
                <div><label for="register-email" class="mb-1.5 block text-sm font-medium">Email address <span class="text-rose-700" aria-hidden="true">*</span></label><input id="register-email" name="email" type="email" wire:model.blur="email" autocomplete="email" required class="storefront-input">@error('email')<p class="mt-1.5 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror</div>
                <div><label for="register-password" class="mb-1.5 block text-sm font-medium">Password <span class="text-rose-700" aria-hidden="true">*</span></label><input id="register-password" name="password" type="password" wire:model="password" autocomplete="new-password" required minlength="8" class="storefront-input">@error('password')<p class="mt-1.5 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror</div>
                <div><label for="register-password-confirmation" class="mb-1.5 block text-sm font-medium">Confirm password <span class="text-rose-700" aria-hidden="true">*</span></label><input id="register-password-confirmation" name="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" required minlength="8" class="storefront-input">@error('password_confirmation')<p class="mt-1.5 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>@enderror</div>
                <label class="flex min-h-11 items-center gap-3 text-sm text-stone-700 dark:text-stone-200"><input type="checkbox" name="marketing_opt_in" wire:model="marketingOptIn" class="size-4 rounded border-stone-300 text-blue-700 focus:ring-blue-700">Subscribe to marketing emails</label>
                <button type="submit" wire:loading.attr="disabled" class="mt-1 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-stone-950 px-6 text-sm font-semibold text-white hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 disabled:opacity-60 dark:bg-white dark:text-stone-950 dark:hover:bg-blue-200"><span wire:loading.remove wire:target="register">Create account</span><span wire:loading wire:target="register">Creating account…</span></button>
            </form>
            <p class="mt-6 text-center text-sm text-stone-600 dark:text-stone-300">Already have an account? <a href="/account/login" class="font-semibold text-blue-800 underline underline-offset-4 dark:text-blue-300">Log in</a></p>
        </div>
    </section>
</div>
