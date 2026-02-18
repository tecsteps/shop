<div>
    <x-slot:title>Cart</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold">Shopping Cart</h1>

        <div class="mt-8 lg:grid lg:grid-cols-12 lg:gap-x-12">
            {{-- Cart Items --}}
            <div class="lg:col-span-7">
                <div class="rounded-lg border border-gray-200 p-8 text-center dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">Your cart is empty</p>
                    <a href="/collections" class="mt-4 inline-block text-sm font-medium text-blue-600 hover:text-blue-500">
                        Continue Shopping
                    </a>
                </div>
            </div>

            {{-- Order Summary --}}
            <div class="mt-8 lg:col-span-5 lg:mt-0">
                <div class="rounded-lg bg-gray-50 p-6 dark:bg-gray-800">
                    <h2 class="text-lg font-medium">Order Summary</h2>
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                            <span>0.00 USD</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Shipping</span>
                            <span>Calculated at checkout</span>
                        </div>
                        <div class="border-t border-gray-200 pt-2 dark:border-gray-700">
                            <div class="flex justify-between font-medium">
                                <span>Total</span>
                                <span>0.00 USD</span>
                            </div>
                        </div>
                    </div>
                    <button disabled class="mt-6 w-full rounded-lg bg-blue-600 px-8 py-3 text-white opacity-50">
                        Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
