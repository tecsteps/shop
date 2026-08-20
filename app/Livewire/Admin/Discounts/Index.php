<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Livewire\Component;

class Index extends Component
{
    public function render(): mixed
    {
        return view('livewire.admin.discounts.index', ['discounts' => Discount::query()->latest()->get()])->layout('layouts.admin');
    }
}
