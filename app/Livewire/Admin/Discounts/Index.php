<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.admin.layout', ['title' => 'Discounts'])]
class Index extends Component
{
    use WithPagination;

    public function render(): mixed
    {
        $store = app('current_store');

        return view('livewire.admin.discounts.index', [
            'discounts' => Discount::query()
                ->where('store_id', $store->id)
                ->latest()
                ->paginate(15),
        ]);
    }
}
