<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public function render()
    {
        $discounts = Discount::query()->orderByDesc('created_at')->get();

        return view('livewire.admin.discounts.index', compact('discounts'))->title('Discounts');
    }
}
