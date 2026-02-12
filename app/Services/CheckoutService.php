<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\CheckoutStateException;
use App\Exceptions\DiscountValidationException;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShippingRate;
use App\Models\Store;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        private readonly ShippingService $shippingService,
        private readonly PricingService $pricingService,
        private readonly DiscountService $discountService,
        private readonly InventoryService $inventoryService,
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService,
    ) {}

    public function findForStore(Store $store, int $checkoutId): Checkout
    {
        return Checkout::query()
            ->with(['cart.lines.variant.product.collections', 'store', 'shippingMethod'])
            ->where('store_id', $store->id)
            ->whereKey($checkoutId)
            ->firstOrFail();
    }

    public function createFromCart(Cart $cart, string $email): Checkout
    {
        $cart->loadMissing('lines', 'store');

        if ($cart->status !== CartStatus::Active) {
            throw new CheckoutStateException('Cart is not active.', 422, 'cart_not_active');
        }

        if ($cart->lines->isEmpty()) {
            throw new CheckoutStateException('Cart is empty.', 422, 'cart_empty');
        }

        $checkout = Checkout::query()->create([
            'store_id' => $cart->store_id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started,
            'email' => $email,
            'totals_json' => [
                'subtotal' => (int) $cart->lines->sum('line_subtotal_amount'),
                'discount' => (int) $cart->lines->sum('line_discount_amount'),
                'shipping' => 0,
                'tax' => 0,
                'total' => (int) $cart->lines->sum('line_total_amount'),
                'currency' => $cart->currency,
            ],
        ]);

        return $this->refreshCheckout($checkout->id);
    }

    /**
     * @param  array<string, mixed>|null  $shippingAddress
     * @param  array<string, mixed>|null  $billingAddress
     */
    public function setAddress(
        Checkout $checkout,
        ?array $shippingAddress,
        ?array $billingAddress = null,
        bool $useShippingAsBilling = true,
        ?string $email = null,
    ): Checkout {
        $this->assertCanMutate($checkout);

        $checkout = $this->refreshCheckout($checkout->id);

        if ($this->shippingService->requiresShipping($checkout->cart) && ! $shippingAddress) {
            throw new CheckoutStateException('Shipping address is required.', 422, 'shipping_address_required');
        }

        $checkout->email = $email ?: $checkout->email;
        $checkout->shipping_address_json = $shippingAddress;
        $checkout->billing_address_json = $useShippingAsBilling
            ? $shippingAddress
            : ($billingAddress ?: $shippingAddress);
        $checkout->status = CheckoutStatus::Addressed;
        $checkout->save();

        return $this->recalculateTotals($checkout);
    }

    public function setShippingMethod(Checkout $checkout, ?int $shippingMethodId): Checkout
    {
        $this->assertCanMutate($checkout);

        $checkout = $this->refreshCheckout($checkout->id);
        $cart = $checkout->cart;

        if (! $this->shippingService->requiresShipping($cart)) {
            $checkout->shipping_method_id = null;
            $checkout->status = CheckoutStatus::ShippingSelected;
            $checkout->save();

            return $this->recalculateTotals($checkout);
        }

        if (! is_array($checkout->shipping_address_json)) {
            throw new CheckoutStateException('Set shipping address before selecting shipping method.', 422, 'address_required');
        }

        if (! $shippingMethodId) {
            throw new CheckoutStateException('Shipping method is required.', 422, 'shipping_method_required');
        }

        $shippingRate = $this->shippingService->validateRateSelection(
            $checkout->store,
            $cart,
            $checkout->shipping_address_json,
            $shippingMethodId,
        );

        $checkout->shipping_method_id = $shippingRate->id;
        $checkout->status = CheckoutStatus::ShippingSelected;
        $checkout->save();

        return $this->recalculateTotals($checkout);
    }

    public function selectPaymentMethod(Checkout $checkout, PaymentMethod|string $paymentMethod): Checkout
    {
        $this->assertCanMutate($checkout);

        $checkout = $this->refreshCheckout($checkout->id);

        if (! in_array($checkout->status, [CheckoutStatus::ShippingSelected, CheckoutStatus::Addressed], true)) {
            throw new CheckoutStateException('Checkout is not ready for payment method selection.', 422, 'invalid_checkout_state');
        }

        $method = $paymentMethod instanceof PaymentMethod
            ? $paymentMethod
            : PaymentMethod::from((string) $paymentMethod);

        DB::transaction(function () use ($checkout, $method): void {
            $checkout = $this->refreshCheckout($checkout->id);

            foreach ($checkout->cart->lines as $line) {
                $this->inventoryService->reserve($line->variant, $line->quantity);
            }

            $checkout->payment_method = $method;
            $checkout->status = CheckoutStatus::PaymentSelected;
            $checkout->expires_at = now()->addDay();
            $checkout->save();
        });

        return $this->recalculateTotals($checkout);
    }

    /**
     * @throws DiscountValidationException
     */
    public function applyDiscountCode(Checkout $checkout, string $code): Checkout
    {
        $this->assertCanMutate($checkout);

        $checkout = $this->refreshCheckout($checkout->id);
        $applied = $this->discountService->applyCodeToCart($checkout->cart, $code);

        /** @var Discount $discount */
        $discount = $applied['discount'];
        $checkout->discount_code = $discount->code;
        $checkout->save();

        return $this->recalculateTotals($checkout, $discount);
    }

    public function removeDiscountCode(Checkout $checkout): Checkout
    {
        $this->assertCanMutate($checkout);

        $checkout = $this->refreshCheckout($checkout->id);
        $this->discountService->clearCartDiscount($checkout->cart);

        $checkout->discount_code = null;
        $checkout->save();

        return $this->recalculateTotals($checkout, null);
    }

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function placeOrder(Checkout $checkout, array $paymentMethodData = []): Order
    {
        $checkout = $this->refreshCheckout($checkout->id);
        $this->assertPayable($checkout);

        $existingOrder = $this->resolveOrderFromCheckoutSnapshot($checkout);

        if ($existingOrder) {
            return $existingOrder;
        }

        $discount = $checkout->discount_code
            ? $this->discountService->findCode($checkout->store, $checkout->discount_code)
            : null;

        $checkout = $this->recalculateTotals($checkout, $discount);

        $result = $this->paymentService->charge($checkout, $paymentMethodData);

        if (! (bool) ($result['success'] ?? false)) {
            foreach ($checkout->cart->lines as $line) {
                $this->inventoryService->release($line->variant, $line->quantity);
            }

            throw new PaymentFailedException(
                (string) ($result['error_code'] ?? 'payment_failed'),
                (string) ($result['error_message'] ?? 'Payment failed.'),
            );
        }

        /** @var Order $order */
        $order = DB::transaction(function () use ($checkout, $discount, $result): Order {
            $checkout = $this->refreshCheckout($checkout->id);

            $existingOrder = $this->resolveOrderFromCheckoutSnapshot($checkout);

            if ($existingOrder) {
                return $existingOrder;
            }

            $paymentMethod = $checkout->payment_method instanceof PaymentMethod
                ? $checkout->payment_method
                : PaymentMethod::from((string) $checkout->payment_method);

            $captured = in_array($paymentMethod, [PaymentMethod::CreditCard, PaymentMethod::Paypal], true);

            $orderStatus = $captured ? OrderStatus::Paid : OrderStatus::Pending;
            $financialStatus = $captured ? FinancialStatus::Paid : FinancialStatus::Pending;
            $paymentStatus = $captured ? PaymentStatus::Captured : PaymentStatus::Pending;

            $totals = (array) $checkout->totals_json;

            $order = $this->orderService->createFromCheckout(
                $checkout,
                $totals,
                $paymentMethod,
                $orderStatus,
                $financialStatus,
            );

            $this->orderService->createLinesFromCart(
                $order,
                $checkout->cart,
                $discount,
                Arr::wrap($totals['line_taxes'] ?? []),
            );

            Payment::query()->create([
                'order_id' => $order->id,
                'provider' => (string) ($result['provider'] ?? 'mock'),
                'method' => $paymentMethod,
                'provider_payment_id' => (string) ($result['reference_id'] ?? ''),
                'status' => $paymentStatus,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'raw_json_encrypted' => Crypt::encryptString(json_encode($result, JSON_THROW_ON_ERROR)),
            ]);

            if ($captured) {
                foreach ($checkout->cart->lines as $line) {
                    $this->inventoryService->commit($line->variant, $line->quantity);
                }
            }

            if ($discount) {
                $discount->increment('usage_count');
            }

            $checkout->cart->status = CartStatus::Converted;
            $checkout->cart->save();

            $totals = (array) $checkout->totals_json;
            $totals['order_id'] = $order->id;

            $checkout->status = CheckoutStatus::Completed;
            $checkout->totals_json = $totals;
            $checkout->save();

            return $order;
        });

        return $order->fresh(['lines', 'payments', 'customer']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function availableShippingMethods(Checkout $checkout): array
    {
        $checkout = $this->refreshCheckout($checkout->id);

        return $this->shippingService->availableRates(
            $checkout->store,
            $checkout->cart,
            $checkout->shipping_address_json,
        );
    }

    private function recalculateTotals(Checkout $checkout, ?Discount $discount = null): Checkout
    {
        $checkout = $this->refreshCheckout($checkout->id);

        if (! $discount && $checkout->discount_code) {
            $discount = $this->discountService->findCode($checkout->store, $checkout->discount_code);
        }

        $shippingMethod = $checkout->shipping_method_id
            ? ShippingRate::query()->find($checkout->shipping_method_id)
            : null;

        $totals = $this->pricingService->calculate(
            $checkout->cart,
            $checkout->shipping_address_json,
            $shippingMethod,
            $discount,
        );

        $checkout->totals_json = [
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'shipping' => $totals['shipping'],
            'tax' => $totals['tax'],
            'total' => $totals['total'],
            'currency' => $totals['currency'],
            'tax_lines' => $totals['tax_lines'],
            'line_taxes' => $totals['line_taxes'],
            'shipping_tax' => $totals['shipping_tax'],
            'prices_include_tax' => $totals['prices_include_tax'],
        ];
        $checkout->tax_provider_snapshot_json = $totals['tax_provider_snapshot'];
        $checkout->save();

        return $this->refreshCheckout($checkout->id);
    }

    private function assertCanMutate(Checkout $checkout): void
    {
        if ($checkout->status === CheckoutStatus::Completed) {
            throw new CheckoutStateException('Checkout is already completed.', 409, 'checkout_completed');
        }

        if ($checkout->status === CheckoutStatus::Expired || ($checkout->expires_at && $checkout->expires_at->isPast())) {
            throw new CheckoutStateException('Checkout has expired.', 410, 'checkout_expired');
        }
    }

    private function assertPayable(Checkout $checkout): void
    {
        $this->assertCanMutate($checkout);

        if ($checkout->status !== CheckoutStatus::PaymentSelected) {
            throw new CheckoutStateException('Checkout must be in payment_selected state.', 409, 'invalid_checkout_state');
        }

        if (! $checkout->payment_method) {
            throw new CheckoutStateException('Payment method is not selected.', 422, 'payment_method_required');
        }
    }

    private function refreshCheckout(int $checkoutId): Checkout
    {
        return Checkout::query()
            ->with([
                'cart.lines.variant.product.collections',
                'cart.lines.variant.optionValues.option',
                'store',
                'shippingMethod',
            ])
            ->findOrFail($checkoutId);
    }

    private function resolveOrderFromCheckoutSnapshot(Checkout $checkout): ?Order
    {
        $orderId = data_get($checkout->totals_json, 'order_id');

        if (! $orderId) {
            return null;
        }

        return Order::query()->where('store_id', $checkout->store_id)->find($orderId);
    }
}
