<?php

namespace App\View\Components;

use App\Support\MoneyFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Price extends Component
{
    public string $formatted;

    public function __construct(
        public int $cents,
        public string $currency = 'EUR',
    ) {
        $this->formatted = MoneyFormatter::format($this->cents, $this->currency);
    }

    public function render(): View
    {
        return view('components.price');
    }
}
