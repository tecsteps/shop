<?php

namespace App\Support;

trait CurrencyFormatter
{
    public function formatCurrency(int $amountInCents): string
    {
        return '$'.number_format($amountInCents / 100, 2);
    }
}
