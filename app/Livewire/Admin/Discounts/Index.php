<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $typeFilter = '';

    public int $perPage = 20;

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $discounts = Discount::query()
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== '', fn ($q) => $q->where('type', $this->typeFilter))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.discounts.index', [
            'discounts' => $discounts,
        ]);
    }
}
