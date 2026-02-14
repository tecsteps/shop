<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Checkout;
use RuntimeException;

final class InvalidCheckoutStateException extends RuntimeException
{
    /**
     * @param  list<string>  $expectedStates
     */
    public function __construct(
        public readonly string $reasonCode,
        public readonly int $checkoutId,
        public readonly ?string $currentState,
        public readonly array $expectedStates,
        string $message,
    ) {
        parent::__construct($message);
    }

    /**
     * @param  list<string>  $expectedStates
     */
    public static function invalidTransition(Checkout $checkout, array $expectedStates): self
    {
        return new self(
            reasonCode: 'invalid_checkout_state',
            checkoutId: (int) $checkout->id,
            currentState: $checkout->status?->value,
            expectedStates: $expectedStates,
            message: sprintf(
                'Checkout %d is in state "%s". Expected one of: %s.',
                (int) $checkout->id,
                $checkout->status?->value ?? 'unknown',
                implode(', ', $expectedStates),
            ),
        );
    }

    public static function immutable(Checkout $checkout): self
    {
        return new self(
            reasonCode: 'checkout_immutable',
            checkoutId: (int) $checkout->id,
            currentState: $checkout->status?->value,
            expectedStates: [],
            message: sprintf('Checkout %d is immutable in state "%s".', (int) $checkout->id, $checkout->status?->value ?? 'unknown'),
        );
    }

    public static function emptyCart(int $cartId): self
    {
        return new self(
            reasonCode: 'empty_cart',
            checkoutId: 0,
            currentState: null,
            expectedStates: [],
            message: sprintf('Cart %d is empty and cannot be checked out.', $cartId),
        );
    }

    public static function cartNotActive(int $cartId, string $currentStatus): self
    {
        return new self(
            reasonCode: 'cart_not_active',
            checkoutId: 0,
            currentState: $currentStatus,
            expectedStates: ['active'],
            message: sprintf('Cart %d is "%s" and cannot be checked out.', $cartId, $currentStatus),
        );
    }

    public static function shippingAddressRequired(int $checkoutId): self
    {
        return new self(
            reasonCode: 'shipping_address_required',
            checkoutId: $checkoutId,
            currentState: null,
            expectedStates: [],
            message: sprintf('Checkout %d requires a shipping address before selecting a shipping method.', $checkoutId),
        );
    }

    public static function unserviceableAddress(int $checkoutId): self
    {
        return new self(
            reasonCode: 'unserviceable_address',
            checkoutId: $checkoutId,
            currentState: null,
            expectedStates: [],
            message: sprintf('Checkout %d cannot ship to the provided address.', $checkoutId),
        );
    }

    public static function invalidShippingMethod(int $checkoutId, int $shippingRateId): self
    {
        return new self(
            reasonCode: 'invalid_shipping_method',
            checkoutId: $checkoutId,
            currentState: null,
            expectedStates: [],
            message: sprintf('Shipping method %d is not available for checkout %d.', $shippingRateId, $checkoutId),
        );
    }

    public static function invalidPaymentMethod(int $checkoutId, string $paymentMethod): self
    {
        return new self(
            reasonCode: 'invalid_payment_method',
            checkoutId: $checkoutId,
            currentState: null,
            expectedStates: [],
            message: sprintf('Payment method "%s" is invalid for checkout %d.', $paymentMethod, $checkoutId),
        );
    }

    public static function missingAddressField(int $checkoutId, string $field): self
    {
        return new self(
            reasonCode: 'missing_address_field',
            checkoutId: $checkoutId,
            currentState: null,
            expectedStates: [],
            message: sprintf('Checkout %d is missing required address field "%s".', $checkoutId, $field),
        );
    }
}
