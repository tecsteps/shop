<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function delete(int $id): void
    {
        Discount::query()->whereKey($id)->delete();
    }

    public function render()
    {
        $query = Discount::query()
            ->when($this->search !== '', fn ($q) => $q->where('code', 'like', '%'.$this->search.'%'))
            ->orderByDesc('id');

        return view('livewire.admin.discounts.index', [
            'discounts' => $query->paginate(20),
        ]);
    }
}
