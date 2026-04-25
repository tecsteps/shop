<?php

namespace App\Services\Shop;

use Illuminate\Validation\ValidationException;

class MockPaymentProvider
{
    /**
     * @return array{status:string,reference:string,instructions?:array<string,string>}
     */
    public function charge(string $method, int $amount, array $payload = []): array
    {
        if ($method === 'credit_card') {
            $number = preg_replace('/\D+/', '', (string) ($payload['card_number'] ?? ''));

            if ($number === '4000000000000002') {
                throw ValidationException::withMessages(['payment' => 'The test card was declined.']);
            }

            if ($number === '4000000000009995') {
                throw ValidationException::withMessages(['payment' => 'The test card has insufficient funds.']);
            }

            return ['status' => 'paid', 'reference' => 'cc_'.str()->upper(str()->random(10))];
        }

        if ($method === 'paypal') {
            return ['status' => 'paid', 'reference' => 'pp_'.str()->upper(str()->random(10))];
        }

        if ($method === 'bank_transfer') {
            return [
                'status' => 'pending',
                'reference' => 'BT-'.str()->upper(str()->random(8)),
                'instructions' => [
                    'iban' => 'DE89370400440532013000',
                    'bic' => 'COBADEFFXXX',
                    'amount' => Money::format($amount),
                ],
            ];
        }

        throw ValidationException::withMessages(['payment_method' => 'Unsupported payment method.']);
    }
}

