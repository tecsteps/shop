<x-storefront.layout :title="'Register'">
    <section class="mx-auto max-w-md px-4 py-10">
        <h1 class="text-3xl font-bold tracking-normal">Register</h1>
        <form method="POST" action="{{ route('account.store') }}" class="mt-6 grid gap-4">
            @csrf
            <label class="grid gap-2"><span>Name</span><input name="name" value="{{ old('name') }}" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
            <label class="grid gap-2"><span>Email</span><input name="email" type="email" value="{{ old('email') }}" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
            <label class="grid gap-2"><span>Password</span><input name="password" type="password" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
            <label class="grid gap-2"><span>Confirm password</span><input name="password_confirmation" type="password" class="rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900"></label>
            <button class="rounded-md bg-zinc-950 px-4 py-3 font-medium text-white dark:bg-white dark:text-zinc-950">Create account</button>
        </form>
    </section>
</x-storefront.layout>

