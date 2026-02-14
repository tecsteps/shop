<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\CartStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidCheckoutStateException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class CartController extends StorefrontController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
    ) {}

    public function show(Request $request): View
    {
        $storeId = $this->currentStoreId($request);

        $cart = $this->resolveCart($request, $storeId);
        $lines = $cart?->lines ?? collect();

        $subtotal = (int) $lines->sum(fn (CartLine $line): int => (int) $line->line_subtotal_amount);
        $discount = (int) $lines->sum(fn (CartLine $line): int => (int) $line->line_discount_amount);
        $total = (int) $lines->sum(fn (CartLine $line): int => (int) $line->line_total_amount);
        $itemCount = (int) $lines->sum(fn (CartLine $line): int => (int) $line->quantity);

        return view('storefront.cart.show', [
            'cart' => $cart,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'itemCount' => $itemCount,
        ]);
    }

    public function addLine(Request $request): RedirectResponse
    {
        try {
            $validated = Validator::make($request->all(), [
                'variant_id' => ['required', 'integer', 'min:1'],
                'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
                'cart_version' => ['nullable', 'integer', 'min:1'],
                'expected_version' => ['nullable', 'integer', 'min:1'],
            ])->validate();
        } catch (ValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors())->withInput();
        }

        $store = $this->currentStoreModel($request);
        $cart = $this->resolveCart($request, (int) $store->id);

        if (! $cart instanceof Cart) {
            $cart = $this->createCart($request, $store);
        }

        $expectedVersion = $this->expectedCartVersion($validated);

        try {
            $this->cartService->addLine(
                cart: $cart,
                variantId: (int) $validated['variant_id'],
                quantity: (int) $validated['quantity'],
                expectedVersion: $expectedVersion,
            );
        } catch (ModelNotFoundException) {
            return redirect()->back()->withErrors([
                'variant_id' => 'The selected variant is invalid.',
            ])->withInput();
        } catch (InsufficientInventoryException) {
            return redirect()->back()->withErrors([
                'quantity' => 'The selected quantity exceeds available inventory.',
            ])->withInput();
        } catch (CartVersionMismatchException) {
            return redirect()->back()->withErrors([
                'cart' => 'Your cart changed in another tab. Refresh and try again.',
            ])->withInput();
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withErrors([
                'quantity' => $exception->getMessage(),
            ])->withInput();
        } catch (Throwable) {
            return redirect()->back()->withErrors([
                'cart' => 'Unable to add item to cart right now.',
            ])->withInput();
        }

        $request->session()->put('cart_id', (int) $cart->id);

        return redirect()->back()->with('status', 'Item added to cart.');
    }

    public function updateLine(Request $request, int $lineId): RedirectResponse
    {
        try {
            $validated = Validator::make($request->all(), [
                'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
                'cart_version' => ['required_without:expected_version', 'integer', 'min:1'],
                'expected_version' => ['required_without:cart_version', 'integer', 'min:1'],
            ])->validate();
        } catch (ValidationException $exception) {
            return redirect()->route('storefront.cart.show')->withErrors($exception->errors())->withInput();
        }

        $cart = $this->resolveCart($request, $this->currentStoreId($request));

        if (! $cart instanceof Cart) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'cart' => 'Cart not found.',
            ]);
        }

        $line = $cart->lines->firstWhere('id', $lineId);

        if (! $line instanceof CartLine) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'line' => 'Cart line not found.',
            ]);
        }

        try {
            $this->cartService->updateLineQuantity(
                cart: $cart,
                lineId: (int) $line->id,
                quantity: (int) $validated['quantity'],
                expectedVersion: $this->expectedCartVersion($validated),
            );
        } catch (InsufficientInventoryException) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'quantity' => 'The selected quantity exceeds available inventory.',
            ])->withInput();
        } catch (CartVersionMismatchException) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'cart' => 'Your cart changed in another tab. Refresh and try again.',
            ])->withInput();
        } catch (ModelNotFoundException) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'line' => 'Cart line not found.',
            ])->withInput();
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'quantity' => $exception->getMessage(),
            ])->withInput();
        } catch (Throwable) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'cart' => 'Unable to update this line right now.',
            ])->withInput();
        }

        return redirect()->route('storefront.cart.show')->with('status', 'Cart line updated.');
    }

    public function removeLine(Request $request, int $lineId): RedirectResponse
    {
        try {
            $validated = Validator::make($request->all(), [
                'cart_version' => ['required_without:expected_version', 'integer', 'min:1'],
                'expected_version' => ['required_without:cart_version', 'integer', 'min:1'],
            ])->validate();
        } catch (ValidationException $exception) {
            return redirect()->route('storefront.cart.show')->withErrors($exception->errors())->withInput();
        }

        $cart = $this->resolveCart($request, $this->currentStoreId($request));

        if (! $cart instanceof Cart) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'cart' => 'Cart not found.',
            ]);
        }

        $line = $cart->lines->firstWhere('id', $lineId);

        if (! $line instanceof CartLine) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'line' => 'Cart line not found.',
            ]);
        }

        try {
            $this->cartService->removeLine(
                cart: $cart,
                lineId: (int) $line->id,
                expectedVersion: $this->expectedCartVersion($validated),
            );
        } catch (CartVersionMismatchException) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'cart' => 'Your cart changed in another tab. Refresh and try again.',
            ])->withInput();
        } catch (ModelNotFoundException) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'line' => 'Cart line not found.',
            ]);
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'cart' => $exception->getMessage(),
            ]);
        } catch (Throwable) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'cart' => 'Unable to remove this line right now.',
            ]);
        }

        return redirect()->route('storefront.cart.show')->with('status', 'Item removed from cart.');
    }

    public function startCheckout(Request $request): RedirectResponse
    {
        $storeId = $this->currentStoreId($request);
        $cart = $this->resolveCart($request, $storeId);

        if (! $cart instanceof Cart) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'cart' => 'Cart not found.',
            ]);
        }

        if ($cart->lines->isEmpty()) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'cart' => 'Cannot create checkout from an empty cart.',
            ]);
        }

        $checkoutEmail = $this->checkoutEmail($cart);

        try {
            $checkout = $this->checkoutService->createFromCart($cart, $checkoutEmail);
        } catch (InvalidCheckoutStateException|InvalidArgumentException $exception) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'cart' => $exception->getMessage(),
            ]);
        } catch (Throwable) {
            $checkout = Checkout::query()->create([
                'store_id' => (int) $cart->store_id,
                'cart_id' => (int) $cart->id,
                'customer_id' => $cart->customer_id,
                'status' => 'started',
                'email' => $checkoutEmail,
                'totals_json' => [
                    'subtotal' => (int) $cart->lines->sum('line_subtotal_amount'),
                    'discount' => (int) $cart->lines->sum('line_discount_amount'),
                    'shipping' => 0,
                    'tax' => 0,
                    'total' => (int) $cart->lines->sum('line_total_amount'),
                    'currency' => (string) $cart->currency,
                ],
                'expires_at' => now()->addDay(),
            ]);
        }

        return redirect()->route('storefront.checkout.show', ['checkoutId' => $checkout->id])
            ->with('status', 'Checkout started.');
    }

    private function createCart(Request $request, Store $store): Cart
    {
        $customer = null;
        $customerId = Auth::guard('customer')->id();

        if (is_numeric($customerId)) {
            $resolvedCustomer = Customer::query()->find((int) $customerId);

            if ($resolvedCustomer instanceof Customer) {
                $customer = $resolvedCustomer;
            }
        }

        try {
            $cart = $this->cartService->create($store, $customer);
        } catch (Throwable) {
            $cart = Cart::query()->create([
                'store_id' => (int) $store->id,
                'customer_id' => $customer?->id,
                'currency' => (string) $store->default_currency,
                'cart_version' => 1,
                'status' => CartStatus::Active->value,
            ]);
        }

        $request->session()->put('cart_id', (int) $cart->id);

        return $cart;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function expectedCartVersion(array $validated): ?int
    {
        $value = $validated['cart_version'] ?? $validated['expected_version'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    private function checkoutEmail(Cart $cart): ?string
    {
        if (is_numeric($cart->customer_id)) {
            $customer = Customer::query()->find((int) $cart->customer_id);

            if ($customer instanceof Customer && is_string($customer->email) && $customer->email !== '') {
                return $customer->email;
            }
        }

        $guardCustomer = Auth::guard('customer')->user();

        if ($guardCustomer instanceof Customer && is_string($guardCustomer->email) && $guardCustomer->email !== '') {
            return $guardCustomer->email;
        }

        return null;
    }

    private function resolveCart(Request $request, int $storeId): ?Cart
    {
        $cartId = $request->session()->get('cart_id');

        $query = Cart::query()
            ->where('store_id', $storeId)
            ->where('status', CartStatus::Active->value)
            ->with([
                'lines' => fn ($lineQuery) => $lineQuery
                    ->orderByDesc('id')
                    ->with([
                        'variant' => fn ($variantQuery) => $variantQuery->with('product'),
                    ]),
            ]);

        if (is_numeric($cartId)) {
            $cart = (clone $query)->whereKey((int) $cartId)->first();

            if ($cart instanceof Cart) {
                return $cart;
            }
        }

        $customerId = Auth::guard('customer')->id();

        if (! is_numeric($customerId)) {
            return null;
        }

        $customerCart = (clone $query)
            ->where('customer_id', (int) $customerId)
            ->orderByDesc('updated_at')
            ->first();

        if ($customerCart instanceof Cart) {
            $request->session()->put('cart_id', (int) $customerCart->id);
        }

        return $customerCart;
    }
}
