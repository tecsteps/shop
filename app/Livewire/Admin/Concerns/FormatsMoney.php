<?php

namespace App\Livewire\Admin\Concerns;

trait FormatsMoney
{
    /**
     * Format an amount stored in minor units (cents) as a currency string.
     */
    public function formatMoney(int $amount): string
    {
        $currency = app()->bound('current_store') ? app('current_store')->default_currency : 'USD';

        return number_format($amount / 100, 2, '.', ',').' '.$currency;
    }
}
