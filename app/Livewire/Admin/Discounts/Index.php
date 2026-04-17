<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $statusFilter = '';

    public string $typeFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Discount::class);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $this->authorize('delete', Discount::class);

        $discount = Discount::findOrFail($id);
        $discount->delete();

        session()->flash('toast', ['type' => 'success', 'message' => __('Discount deleted.')]);
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

        return view('livewire.admin.discounts.index', [
            'discounts' => $query->latest()->paginate(15),
        ]);
    }
}
