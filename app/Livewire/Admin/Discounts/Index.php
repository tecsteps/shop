<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Models\Discount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<Discount>
     */
    #[Computed]
    public function discounts(): LengthAwarePaginator
    {
        $query = Discount::query();

        if ($this->search !== '') {
            $query->where('code', 'like', "%{$this->search}%");
        }

        if ($this->statusFilter !== 'all') {
            $status = DiscountStatus::tryFrom($this->statusFilter);
            if ($status) {
                $query->where('status', $status);
            }
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function render()
    {
        return view('livewire.admin.discounts.index')
            ->layout('layouts.admin', ['title' => 'Discounts']);
    }
}
