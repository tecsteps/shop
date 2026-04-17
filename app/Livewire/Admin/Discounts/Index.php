<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): mixed
    {
        $discounts = Discount::query()
            ->when($this->search, fn ($q) => $q->where('code', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.discounts.index', ['discounts' => $discounts])
            ->layout('layouts.admin.app', ['title' => 'Discounts']);
    }
}
