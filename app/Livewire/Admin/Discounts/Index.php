<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public string $typeFilter = '';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Discount::query();

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->typeFilter !== '') {
            $query->where('type', $this->typeFilter);
        }

        $discounts = $query->latest()->paginate(15);

        return view('livewire.admin.discounts.index', [
            'discounts' => $discounts,
        ]);
    }
}
