<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
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

    #[Computed]
    public function discounts(): mixed
    {
        $query = Discount::query();

        if ($this->search !== '') {
            $query->where('code', 'like', "%{$this->search}%");
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->latest('created_at')->paginate(15);
    }

    public function render(): mixed
    {
        return view('livewire.admin.discounts.index')
            ->layout('layouts.admin', ['breadcrumbs' => [['label' => 'Discounts']]]);
    }
}
