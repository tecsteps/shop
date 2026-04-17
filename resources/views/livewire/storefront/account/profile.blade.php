<div class="flex flex-col gap-8">
    <header>
        <h1 class="text-3xl font-semibold tracking-tight">Profile</h1>
    </header>

    @if ($status)
        <div class="rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
            {{ $status }}
        </div>
    @endif

    <section class="flex flex-col gap-4 rounded-lg border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
        <h2 class="text-lg font-semibold">Your information</h2>
        <form wire:submit="saveProfile" class="flex flex-col gap-3">
            <flux:input wire:model="name" label="Name" required />
            <flux:input wire:model="email" type="email" label="Email" required />
            <flux:checkbox wire:model="marketing_opt_in" label="Email me about news and offers" />
            <div>
                <flux:button type="submit" variant="primary">Save profile</flux:button>
            </div>
        </form>
    </section>

    <section class="flex flex-col gap-4 rounded-lg border border-neutral-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900">
        <h2 class="text-lg font-semibold">Change password</h2>
        <form wire:submit="changePassword" class="flex flex-col gap-3">
            <flux:input wire:model="current_password" type="password" label="Current password" required />
            <flux:input wire:model="password" type="password" label="New password" required />
            <flux:input wire:model="password_confirmation" type="password" label="Confirm new password" required />
            <div>
                <flux:button type="submit" variant="primary">Update password</flux:button>
            </div>
        </form>
    </section>
</div>
