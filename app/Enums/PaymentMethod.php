<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CreditCard = 'credit_card';
    case Paypal = 'paypal';
    case BankTransfer = 'bank_transfer';

    /**
     * Whether the method captures instantly at checkout (spec 05 section 10.2).
     */
    public function capturesInstantly(): bool
    {
        return $this !== self::BankTransfer;
    }

    public function label(): string
    {
        return match ($this) {
            self::CreditCard => __('Credit Card'),
            self::Paypal => __('PayPal'),
            self::BankTransfer => __('Bank Transfer'),
        };
    }
}
