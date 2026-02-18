<x-layouts::storefront>
    <x-slot:title>Order Confirmed</x-slot:title>

    <div class="mx-auto max-w-2xl px-4 py-16 text-center sm:px-6 lg:px-8">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
            <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="mt-6 text-3xl font-bold">Thank you for your order!</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Your order has been placed successfully.</p>
        <a href="/" class="mt-8 inline-block rounded-lg bg-blue-600 px-8 py-3 text-white hover:bg-blue-700">
            Continue Shopping
        </a>
    </div>
</x-layouts::storefront>
